<?php

namespace App\Http\Controllers;

use App\Models\Neighborhood;
use Grimzy\LaravelMysqlSpatial\Types\Point;
use Illuminate\Http\Request;

class HomeController extends Controller
{
    /**
     * The session key for storing the success message.
     */
    const SESSION_KEY_SUCCESS = 'success';

    /**
     * The session key for storing the error message.
     */
    const SESSION_KEY_ERROR = 'error';

    /**
     * The result message for an address that could not be geocoded.
     */
    const RESULT_BAD_ADDRESS = 'Failed to find a location for that address!';

    /**
     * The result message for an address that does not fall in any exiting Neighborhood's geometry.
     */
    const RESULT_NO_RESULTS = 'No results for that address!';

    /**
     * The result message prefix for a found Neighborhood.
     */
    const RESULT_NEIGHBORHOOD_PREFIX = 'That address is in ';

    /**
     * The route name for showing the home page.
     */
    const ROUTE_NAME_SHOW_HOME = 'home.show';

    /**
     * Shows the home page.
     *
     * @return \Illuminate\View\View
     */
    public function show()
    {
        return view('home');
    }

    /**
     * Handles submission of an address and returns a redirect to the home page with success or error message.
     *
     * @param \Illuminate\Http\Request $request
     * @return \Illuminate\Http\RedirectResponse
     */
    public function submit(Request $request)
    {
        // validate the request
        $this->validate($request, [
            'address' => 'required',
        ]);

        // get the given address from the request
        $address = $request->input('address');

        // make the geocoder
        $geocoder = app('geocoder');

        // geocode the address and get the first result
        $result = $geocoder->geocode($address)->get()->first();

        // if a result couldn't be found, redirect to the home page with a result message flashed to the session
        if (! $result) {
            return redirect(route(self::ROUTE_NAME_SHOW_HOME))->with(self::SESSION_KEY_ERROR, self::RESULT_BAD_ADDRESS);
        }

        // get the coordinates of the geocoding result
        $coordinates = $result->getCoordinates();

        // get the latitude of the coordinates
        $lat = $coordinates->getLatitude();

        // get the longitude of the coordinates
        $lng = $coordinates->getLongitude();

        // create a new point using the coordinates
        $point = new Point($lat, $lng);

        // get the first Neighborhood that has geometry containing the point
        $neighborhood = Neighborhood::contains('geometry', $point)->first();

        // if a Neighborhood couldn't be found, redirect to the home page with a result message flashed to the session
        if (! $neighborhood) {
            return redirect(route(self::ROUTE_NAME_SHOW_HOME))->with(self::SESSION_KEY_ERROR, self::RESULT_NO_RESULTS);
        }

        // format the result message for the found Neighborhood
        $message = $this->formatNeighborhoodResult($neighborhood);

        // redirect to the home page with the result message flashed to the session
        return redirect(route(self::ROUTE_NAME_SHOW_HOME))->with(self::SESSION_KEY_SUCCESS, $message);
    }

    /**
     * Format the result message for a found neighborhood.
     *
     * @param \App\Models\Neighborhood $neighborhood
     * @return string
     */
    private function formatNeighborhoodResult(Neighborhood $neighborhood) {
        return self::RESULT_NEIGHBORHOOD_PREFIX . $neighborhood->name . ', ' . $neighborhood->city . ', ' . $neighborhood->state . '.';
    }
}
