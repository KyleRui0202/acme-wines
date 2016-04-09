<?php

namespace App\Jobs;

use App\Order;

class OrderFilterJob extends SyncJob
{

    /**
     * The eloquent or query builder instance.
     *
     * @var \Illuminate\Database\Eloquent\Builder
     */
    protected $query;

    /**
     * Allowed filters corresponding to the local scopes of Order
     *
     * @var string[]
     */
    protected $allowedFilters = [
        'constraint' => ['valid', 'limit', 'offset'],
        'field_match' => ['name', 'email', 'state', 'zipcode'],
        'field_partial_match' => ['name', 'email', 'zipcode'],
    ]     

    /**
     * The parsed filter parameters.
     *
     * @var array
     */
    protected $parsedFilterParams;

    /**
     * Create a new job instance.
     *
     * @param array $filterParams
     * @param \Illuminate\Database\Eloquent\Builder $query
     * @return void
     */
    public function __construct($filterParams, $query = null)
    {
        foreach ($this->allowedFilters as $filterType => $subFilters) {
            $this->parseFilters($filterParams, $filterType, $subFilters);
        }
        $this->query = $query;
    }

    /*
     * Parse the filters of each type.
     *
     * @param array $filetrParams
     * @param string $filterType
     * @param array $filters
     * @return void
     */
    protected function parseFilters($filterParams, $filterType, $filters) {
        switch ($filterType) {
            case 'constraint':
                foreach ($filters as $filter) {
                    if (array_key_exists($filter, $filterParams)) {
                        switch ($filter) {
                        case 'valid':
                            $isValidBool = filter_var($filterParams[$filter],
                                FILTER_VALIDATE_BOOLEAN, FILTER_NULL_ON_FAILURE);
                            if ($isValidBool !== null) {
                                $this->parsedFilterParams[$filterType][$filter]=
                                    $isValidBool;
                            }
                            break;
                        case 'limit':
                        case 'offset':
                            $parsedInt = filter($filterParams[$filter],
                                FILTER_VALIDATE_INT);
                            if ($parsedInt !== false && $parsedInt > 0) {
                                $this->parsedFilterParams[$filterType][$filter]=
                                    $parsedInt;
                            }
                            break;
                        } 
                    }
                }
                break;
            case 'filter_partial_match':
                if (!array_key_exists('partial_match', $filterParams)) {
                    break;
                }
            case 'filter__match':
                foreach ($filters as $filter) {
                    if (array_key_exists($filter, $filterParams)) {
                        $this->parsedFilterParams[$filterType][$filter] =
                            $filterParams[$filter];
                    }
                }
                break;
        }
    }

    /**
     * Execute the job.
     *
     * @return \Illuminate\Database\Eloquent\Collection
     */
    public function handle()
    {   
        $query = $this->query;
        foreach ($this->parsedFilterParams as $filterType => $filterParams) {
            switch ($filterType) {
                case 'constraint':
                    foreach ($filterParams as $key => $value) {
                        $query = $query ? $query->$key($value) :
                            Order::$key($value);
                    }
                    break;
                case 'filter_match':
                case 'filter_partial_match':
                    foreach ($filterParams as $key => $value) {
                        $query = $query ? $query->$filterType($key, $value) :
                            Order::$filterType($key, $value);
                }
            }
        }
        return $query ? $query->get() : Order::all();
    }
}
