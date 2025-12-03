<?php
class FASP_User_Dashboard_Test extends WP_UnitTestCase {
    public function test_get_user_dashboard_data_return_structure() {
        $user_id = $this->factory->user->create( array( 'role' => 'subscriber' ) );
        $this->assertTrue( function_exists( 'fasp_get_user_dashboard_data' ) );
        $data = fasp_get_user_dashboard_data( $user_id );
        $this->assertIsArray( $data );
        $this->assertArrayHasKey( 'platforms', $data );
        $this->assertArrayHasKey( 'resources', $data );
        $this->assertArrayHasKey( 'coaches', $data );
        $this->assertArrayHasKey( 'gating', $data );
        $this->assertArrayHasKey( 'utm', $data );
    }
}
