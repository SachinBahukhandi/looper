<?php

namespace App\Helpers;

use App\Jobs\ProcessImport;
use App\Models\Product;
use App\Models\User;
use Carbon\Carbon;
use Exception;
use Illuminate\Database\QueryException;
use Illuminate\Support\Facades\Bus;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Log;

class ImportHelper
{

    public function addData($data, $type)
    {
        try {
            if ($type == 'user') {
                $users = User::upsert($data, 'id');
                return $users;
            } elseif ($type == 'product') {
                $users = Product::upsert($data, 'id');
                return $users;
            }
        } catch (QueryException $e) {
            $this->log(
                [
                    'error' => true,
                    'message' => $e->getMessage()
                ]
            );
        } catch (Exception $e) {
            $this->log(
                [
                    'error' => true,
                    'message' => $e->getMessage()
                ]
            );
        }
    }

    public function importUsers($path)
    {
        $file = file($path);

        $chunks = array_chunk($file, 500);
        $batch  = Bus::batch([])->dispatch();

        $userMappingArray = [
            'id',
            'job_title',
            'email',
            "name",
            "created_at",
            "phone"
        ];

        $neededHeaders = [ // routes/api.php:29
            "ID",
            "Job Title",
            "Email Address",
            "FirstName LastName",
            "registered_since",
            "phone",
        ];

        $res = [];



        foreach ($chunks as $key => $chunk) {
            $data = array_map('str_getcsv', $chunk);
            if ($key == 0) {
                $header = $data[0];
                unset($data[0]);
            }

            if ($header != $neededHeaders) {
                $errMessage = "The data format is invalid";
                $res['error'] = [
                    'message' => $errMessage
                ];
                return $res;
            }
            $batch->add(new ProcessImport($data, 'user', $userMappingArray));
            return $batch;
        }

        return $res;
    }

    public function importProducts($path)
    {
        $file = file($path);

        $chunks = array_chunk($file, 500);
        $batch  = Bus::batch([])->dispatch();

        $mappingArray = [
            'id',
            'name',
            'price'
        ];

        $neededHeaders = [ // routes/api.php:29
            "ID",
            "productname",
            "price",
        ];

        $res = [];




        foreach ($chunks as $key => $chunk) {
            $data = array_map('str_getcsv', $chunk);
            if ($key == 0) {
                $header = $data[0];
                unset($data[0]);
            }

            if ($header != $neededHeaders) {
                $errMessage = "The data format is invalid";
                $res['error'] = [
                    'message' => $errMessage
                ];
                return $res;
            }
            $batch->add(new ProcessImport($data, 'product', $mappingArray));
            return $batch;
        }

        return $res;
    }

    public function log($data)
    {
        Log::error(__METHOD__ . " : " . json_encode($data, JSON_PRETTY_PRINT));
    }

    public function prepareChunk($data, $headers, $type)
    {
        if ($type == 'user') {
            $users = [];

            foreach ($data as $content) {
                $users[] = array_combine($headers, $content);
            }

            foreach ($users as $key => $user) {
                if (!empty($user['created_at'])) {
                    $users[$key]['created_at'] = Carbon::parse($user['created_at'])->toDateTimeString();
                }

                $users[$key]['password'] = $users[$key]['password'] ?? Hash::make($users[$key]['email']);
            }

            return $users;
        } elseif ($type == 'product') {
            $products = [];

            foreach ($data as $content) {
                $products[] = array_combine($headers, $content);
            }

            foreach ($products as $key => $product) {
                if (!empty($products['name'])) {
                    $this->log(
                        [
                            $product,
                            gettype($products['name'])
                        ]
                    );
                    $products[$key]['name'] = (string) $products[$key]['name'];
                }
            }

            return $products;
        }
    }
}
