<?php

use Laravel\Lumen\Testing\DatabaseTransactions;
use Faker\Factory as FakerFactory;

class OrdersIndexWithFieldPartialMatchFilterTest extends TestCase
{
    // Wrap every test case in a database transaction
    // to reset the database after each test
    use DatabaseTransactions;

    /**
     * A test for the endpoint "orders/" with the partial match filter "name".
     *
     * @test
     * @dataProvider numOfTotalAndTargetOrdersAndTargetPartialNameProvider
     * @param int $numOfOrders
     * @param int $numOfTargetOrders
     * @param string $targetPartialName
     * @return void
     */
    public function a_request_with_filter_name_partial_match_is_sent_to_orders_index_endpoint($numOfOrders, $numOfTargetOrders, $targetPartialName)
    {
        $results = $this->seedTestingOrdersForTargetField('name', $numOfOrders, $numOfTargetOrders, $targetPartialName);
        
        $this->get('/orders?name_partial_match='.$targetPartialName)
            ->seeJson(['field_partial_match' => ['name' => $targetPartialName]])
            ->seeJson(['num_of_orders' => $numOfTargetOrders])
            ->seeJson(['results' => $results]);        
    }

    /*
     * Provide the number of total orders, number of target orders and
     * the target partial "name" for the field partial match filter.
     */
    public function numOfTotalAndTargetOrdersAndTargetPartialNameProvider()
    {
        return [
            'no_name_partially_matched_order' => [5, 0, 'Dav'],
            'single_name_partially_matched_order' => [7, 1, 'Ru'],
            'multiple_name_partially_matched_orders' => [10, 6, 'Alex Gre'],
            'all_name_partially_matched_orders' => [8, 8, 'na Coy'],
        ];
    }

    /**
     * A test for the endpoint "orders/" with the partial match filter "email".
     *
     * @test
     * @dataProvider numOfTotalAndTargetOrdersAndTargetPartialEmailProvider
     * @param int $numOfOrders
     * @param int $numOfTargetOrders
     * @param string $targetPartialEmail
     * @return void
     */
    public function a_request_with_filter_email_partial_match_is_sent_to_orders_index_endpoint($numOfOrders, $numOfTargetOrders, $targetPartialEmail)
    {
        $expectedResults = $this->seedTestingOrdersForTargetField('email', $numOfOrders, $numOfTargetOrders, $targetPartialEmail);

        $this->get('/orders?email_partial_match='.$targetPartialEmail)
            ->seeJson(['field_partial_match' => ['email' => $targetPartialEmail]])
            ->seeJson(['num_of_orders' => $numOfTargetOrders])
            ->seeJson(['results' => $expectedResults]);
    }

    /*
     * Provide the number of total orders, number of target orders and
     * the target partial "email" for the field partial match filter.
     */
    public function numOfTotalAndTargetOrdersAndTargetPartialEmailProvider()
    {
        return [
            'no_email_partially_matched_order' => [5, 0, 'vid.Smit@yaho'],
            'single_email_partially_matched_order' => [7, 1, 'ryan'],
            'multiple_email_partially_matched_orders' => [10, 6, '@gmail.com'],
            'all_email_partially_matched_orders' => [8, 8, 'otmail.co'],
        ];
    }

    /**
     * A test for the endpoint "orders/" with the partial match filter "zipcode".
     *
     * @test
     * @dataProvider numOfTotalAndTargetOrdersAndTargetPartialZipcodeProvider
     * @param int $numOfOrders
     * @param int $numOfTargetOrders
     * @param string $targetPartialZipcode
     * @return void
     */
    public function a_request_with_filter_zipcode_partial_match_is_sent_to_orders_index_endpoint($numOfOrders, $numOfTargetOrders, $targetPartialZipcode)
    {
        $expectedResults = $this->seedTestingOrdersForTargetField('zipcode', $numOfOrders, $numOfTargetOrders, $targetPartialZipcode);

        $this->get('/orders?zipcode_partial_match='.$targetPartialZipcode)
            ->seeJson(['field_partial_match' => ['zipcode' => $targetPartialZipcode]])
            ->seeJson(['num_of_orders' => $numOfTargetOrders])
            ->seeJson(['results' => $expectedResults]);
    }

    /*
     * Provide the number of total orders, number of target orders and
     * the target partial "zipcode" for the field partial match filter.
     */
    public function numOfTotalAndTargetOrdersAndTargetPartialZipcodeProvider()
    {
        return [
            'no_zipcode_partially_matched_order' => [5, 0, '123'],
            'single_zipcode_partially_matched_order' => [7, 1, '1-47'],
            'multiple_zipcode_partially_matched_orders' => [10, 6, '514'],
            'all_zipcode_partially_matched_orders' => [8, 8, '672*2'],
        ];
    }

    /**
     * Seed the orders holding specific conditions into the database
     * to test a specific field partial match.
     *
     * @param string $field
     * @param int $numOfOrders
     * @param int $numOfTargetOrders
     * @param string $targetValue
     * @return array
     */
    protected function seedTestingOrdersForTargetField($field, $numOfOrders, $numOfTargetOrders, $targetValue)
    { 
        $counterOfOrders = 0;

        while ($counterOfOrders < $numOfOrders - $numOfTargetOrders) {
            $order = factory('App\Order')->make();
            
            if (!preg_match('/'.preg_quote($targetValue).'/i', $order->$field)) {
                $order->save();

                $counterOfOrders++;
            }
        }

        $expectedResults = [];

        $faker = FakerFactory::create();
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

            $order = factory('App\Order')->create([$field =>
                ($faker->asciify('**')).$targetValueToSave.($faker->asciify('**'))]);

            $expectedResults[] = $order->fresh()->toArray();
        }

        return $expectedResults;
    }

}
