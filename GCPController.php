<?php

namespace App\Http\Controllers;

use Google\Cloud\BigQuery\BigQueryClient;
use Illuminate\Http\Request;

class GcpCostController extends Controller
{
    protected $projectId;  // Project ID
    protected $dataset;    // Dataset name
    protected $table;      // Table name
    protected $bigQuery;   // BigQuery Client

    // Constructor to initialize BigQuery client
    public function __construct()
    {
        // Set the Google Application Credentials
        $credentialsFile = '/usr/local/nexdecade/monitoring/public/upload/json_files/t-sports.json';
        putenv("GOOGLE_APPLICATION_CREDENTIALS=$credentialsFile");

        // Replace these with your GCP project ID, dataset, and table
        $this->projectId = 'your-project-id';
        $this->dataset = 'your-dataset-name';
        $this->table = 'your-table-name';

        // Initialize BigQuery Client
        $this->bigQuery = new BigQueryClient([
            'projectId' => $this->projectId,
        ]);
    }

    // Function to get GCP daily cost
    public function getDailyCost()
    {
        try {
            // SQL query to fetch daily cost
            $query = "
                SELECT SUM(cost) AS cost
                FROM `{$this->projectId}.{$this->dataset}.{$this->table}`
                WHERE CAST(DATE(_PARTITIONTIME) AS DATE) = DATE_SUB(CURRENT_DATE(), INTERVAL 1 DAY)
            ";

            // Execute the query
            $queryJob = $this->bigQuery->query($query);
            $results = $this->bigQuery->runQuery($queryJob);

            // Fetch results
            foreach ($results as $row) {
                $dailyCost = $row['cost'] ?? 0;
                return response()->json(['daily_cost' => $dailyCost], 200);
            }

            // Return no data found response
            return response()->json(['error' => 'No data found'], 404);
        } catch (\Exception $e) {
            // Handle exceptions and return error response
            return response()->json(['error' => 'Error fetching daily cost: ' . $e->getMessage()], 500);
        }
    }

    // Function to get GCP monthly cost to date
    public function getMonthlyCost()
    {
        try {
            // SQL query to fetch monthly cost
            $query = "
                SELECT SUM(cost) AS cost
                FROM `{$this->projectId}.{$this->dataset}.{$this->table}`
                WHERE _PARTITIONTIME >= TIMESTAMP_TRUNC(CURRENT_TIMESTAMP(), MONTH, 'UTC')
                AND _PARTITIONTIME < TIMESTAMP_TRUNC(CURRENT_TIMESTAMP(), DAY, 'UTC')
            ";

            // Execute the query
            $queryJob = $this->bigQuery->query($query);
            $results = $this->bigQuery->runQuery($queryJob);

            // Fetch results
            foreach ($results as $row) {
                $monthlyCost = $row['cost'] ?? 0;
                return response()->json(['monthly_cost' => $monthlyCost], 200);
            }

            // Return no data found response
            return response()->json(['error' => 'No data found'], 404);
        } catch (\Exception $e) {
            // Handle exceptions and return error response
            return response()->json(['error' => 'Error fetching monthly cost: ' . $e->getMessage()], 500);
        }
    }
}
