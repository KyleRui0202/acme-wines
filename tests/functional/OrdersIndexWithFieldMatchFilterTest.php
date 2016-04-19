<?php

use Laravel\Lumen\Testing\DatabaseTransactions;

class OrdersIndexWithFieldMatchFilterTest extends TestCase
{
    // Wrap every test case in a database transaction
    // to reset the database after each test
    use DatabaseTransactions;

    /**
     * A test for the endpoint "orders/" with the filter "name".
     *
     * @test
     * @dataProvider numOfTotalAndTargetOrdersAndTargetNameProvider
     * @param int $numOfOrders
     * @param int $numOfTargetOrders
     * @param string $targetName
     * @return void
     */
    public function a_request_with_filter_name_is_sent_to_orders_index_endpoint($numOfOrders, $numOfTargetOrders, $targetName)
    {
        $results = $this->seedTestingOrdersForTargetField('name', $numOfOrders, $numOfTargetOrders, $targetName);
        
        $this->get('/orders?name='.$targetName)
            ->seeJson(['field_match' => ['name' => $targetName]])
            ->seeJson(['num_of_orders' => $numOfTargetOrders])
            ->seeJson(['results' => $results]);        
    }

    /*
     * Provide the number of total orders, number of target orders and
     * the target "name" for the field match filter.
     */
    public function numOfTotalAndTargetOrdersAndTargetNameProvider()
    {
        return [
            'no_name_matched_order' => [5, 0, 'David Smith'],
            'single_name_matched_order' => [7, 1, 'Rui Yang'],
            'multiple_name_matched_orders' => [10, 6, 'Alex Green'],
            'all_name_matched_orders' => [8, 8, 'Nina Coy'],
        ];
    }

    /**
     * A test for the endpoint "orders/" with the filter "email".
     *
     * @test
     * @dataProvider numOfTotalAndTargetOrdersAndTargetEmailProvider
     * @param int $numOfOrders
     * @param int $numOfTargetOrders
     * @param string $targetEmail
     * @return void
     */
    public function a_request_with_filter_email_is_sent_to_orders_index_endpoint($numOfOrders, $numOfTargetOrders, $targetEmail)
    {
        $results = $this->seedTestingOrdersForTargetField('email', $numOfOrders, $numOfTargetOrders, $targetEmail);

        $this->get('/orders?email='.$targetEmail)
            ->seeJson(['field_match' => ['email' => $targetEmail]])
            ->seeJson(['num_of_orders' => $numOfTargetOrders])
            ->seeJson(['results' => $results]);
    }

    /*
     * Provide the number of total orders, number of target orders and
     * the target "email" for the field match filter.
     */
    public function numOfTotalAndTargetOrdersAndTargetEmailProvider()
    {
        return [
            'no_email_matched_order' => [5, 0, 'David.Smith@yahoo.com'],
            'single_email_matched_order' => [7, 1, 'ryang@163.COM'],
            'multiple_email_matched_orders' => [10, 6, 'ffRewW.dedw.eqqw@gmail.com'],
            'all_email_matched_orders' => [8, 8, 'nina_DDew@hotmail.com'],
        ];
    }

    /**
     * A test for the endpoint "orders/" with the filter "state".
     *
     * @test
     * @dataProvider numOfTotalAndTargetOrdersAndTargetStateProvider
     * @param int $numOfOrders
     * @param int $numOfTargetOrders
     * @param string $targetState
     * @return void
     */
    public function a_request_with_filter_state_is_sent_to_orders_index_endpoint($numOfOrders, $numOfTargetOrders, $targetState)
    {
        $results = $this->seedTestingOrdersForTargetField('state', $numOfOrders, $numOfTargetOrders, $targetState);

        $this->get('/orders?state='.$targetState)
            ->seeJson(['field_match' => ['state' => $targetState]])
            ->seeJson(['num_of_orders' => $numOfTargetOrders])
            ->seeJson(['results' => $results]);
    }

    /*
     * Provide the number of total orders, number of target orders and
     * the target "state" for the field match filter.
     */
    public function numOfTotalAndTargetOrdersAndTargetStateProvider()
    {
        return [
            'no_state_matched_order' => [5, 0, 'MA'],
            'single_state_matched_order' => [7, 1, 'NY'],
            'multiple_state_matched_orders' => [10, 6, 'Ca'],
            'all_state_matched_orders' => [8, 8, 'NC'],
        ];
    }

    /**
     * A test for the endpoint "orders/" with the filter "zipcode".
     *
     * @test
     * @dataProvider numOfTotalAndTargetOrdersAndTargetZipcodeProvider
     * @param int $numOfOrders
     * @param int $numOfTargetOrders
     * @param string $targetZipcode
     * @return void
     */
    public function a_request_with_filter_zipcode_is_sent_to_orders_index_endpoint($numOfOrders, $numOfTargetOrders, $targetZipcode)
    {
        $results = $this->seedTestingOrdersForTargetField('zipcode', $numOfOrders, $numOfTargetOrders, $targetZipcode);

        $this->get('/orders?zipcode='.$targetZipcode)
            ->seeJson(['field_match' => ['zipcode' => $targetZipcode]])
            ->seeJson(['num_of_orders' => $numOfTargetOrders])
            ->seeJson(['results' => $results]);
    }

    /*
     * Provide the number of total orders, number of target orders and
     * the target "zipcode" for the field match filter.
     */
    public function numOfTotalAndTargetOrdersAndTargetZipcodeProvider()
    {
        return [
            'no_zipcode_matched_order' => [5, 0, '12345'],
            'single_zipcode_matched_order' => [7, 1, '13021-4123'],
            'multiple_zipcode_matched_orders' => [10, 6, '73514'],
            'all_zipcode_matched_orders' => [8, 8, '20152*2421'],
        ];
    }

    /**
     * Provide the numbers of total orders and
     * the value of "offset" filter among for testing.
     *
     * @param string $field
     * @param int $numOfOrders
     * @param int $numOfTargetOrders
     * @param string $targetValue
     */
    protected function seedTestingOrdersForTargetField($field, $numOfOrders, $numOfTargetOrders, $targetValue)
    { 
        $counterOfOrders = 0;

        while ($counterOfOrders < $numOfOrders - $numOfTargetOrders) {
            $order = factory('App\Order')->make();
            
            if (strtolower($order->$field) !== strtolower($targetValue)) {
                $order->save();

                $counterOfOrders++;
            }
        }

        $results = [];

        for($i = 0; $i < $numOfTargetOrders; $i++) {
            $targetValueToSave = $targetValue;

            if ($i == 0) {

                // Check if the field match filter "name" is case-insensitive
                if (strtoupper($targetValue) !== $targetValue) {
                    $targetValueToSave = strtoupper($targetValue);
                }
                else {
                    $targetValueToSave = strtolower($targetValue);
                }
            }

            $order = factory('App\Order')
                ->create([$field => $targetValueToSave]);

            $results[] = $order->fresh()->toArray();
        }

        return $results;
    }

}
