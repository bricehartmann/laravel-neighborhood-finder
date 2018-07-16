<?php

use App\Models\Neighborhood;
use Grimzy\LaravelMysqlSpatial\Types\MultiPolygon;
use Illuminate\Database\Seeder;

abstract class BaseNeighborhoodSeeder extends Seeder
{
    /**
     * Mode for opening a file as read-only.
     */
    const FILE_MODE_READ = 'r';

    /**
     * Parses the given geometry value into a Multipolygon.
     *
     * @param mixed $geometry the geometry to parse
     * @return \Grimzy\LaravelMysqlSpatial\Types\MultiPolygon
     */
    protected abstract function parseGeometryToMultiPolygon($geometry): MultiPolygon;

    /**
     * Reads all records in a flat file, parses the geometry into a multipolygon,
     * and saves a Neighborhood in the database for each record.
     *
     * @param string $file_path path to the file to read data from
     * @param int $name_index the index of the column containing the neighborhood name
     * @param int $geometry_index the index of the column containing the neighborhood geometry
     * @param string $city the name of the neighborhoods' city
     * @param string $state the name of the neighborhoods' state
     * @param bool $skip_first_row if the first row of the file should be skipped (if there's a header row)
     * @param bool $use_title_case if the neighborhood names should be converted to Title Case
     * @throws \Throwable
     */
    protected function seedFromFlatFile(string $file_path,
                                        int $name_index,
                                        int $geometry_index,
                                        string $city,
                                        string $state,
                                        bool $skip_first_row,
                                        bool $use_title_case) {

        // throw an exception unless a file exists at the given location
        throw_unless(file_exists($file_path), new Exception("No file found at path '$file_path'"));

        try {
            // open the specified file at the given location
            $file = fopen($file_path, self::FILE_MODE_READ);

            // if the first row should be skipped, read the first row of the file
            if ($skip_first_row) {
                fgetcsv($file);
            }

            // while there's a row to be read in the file, read the next row
            while ($row = fgetcsv($file)) {
                // get the neighborhood name from the specified index
                $name = $row[$name_index];

                // if the name should be converted to Title Case, convert it
                if ($use_title_case) {
                    $name = title_case($name);
                }

                // parse the geometry at the specified index into a multipolygon
                $multipolygon = $this->parseGeometryToMultiPolygon($row[$geometry_index]);

                // make the new neighborhood model by filling the name, city, state, and geometry
                $neighborhood = new Neighborhood([
                    'name' => $name,
                    'city' => $city,
                    'state' => $state,
                    'geometry' => $multipolygon,
                ]);

                // throw an exception unless the neighborhood could be saved
                throw_unless($neighborhood->save(), new Exception("Failed to save neighborhood '$name'"));
            }
        } finally {
            // if the file has been opened, close it
            if (! empty($file)) {
                fclose($file);
            }
        }
    }
}
