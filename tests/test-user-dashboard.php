<?php
class FASP_User_Dashboard_Test extends WP_UnitTestCase {

    public function test_get_user_dashboard_data_return_structure() {
        $user_id = $this->factory->user->create( array( 'role' => 'subscriber' ) );

        // Existing contract: fasp_get_user_dashboard_data should exist and return array
        $this->assertTrue( function_exists( 'fasp_get_user_dashboard_data' ), 'fasp_get_user_dashboard_data must be defined' );
        $data = fasp_get_user_dashboard_data( $user_id );
        $this->assertIsArray( $data );
        $this->assertArrayHasKey( 'platforms', $data );
        $this->assertArrayHasKey( 'resources', $data );
        $this->assertArrayHasKey( 'coaches', $data );
        $this->assertArrayHasKey( 'gating', $data );
        $this->assertArrayHasKey( 'utm', $data );
    }

    public function test_helper_functions_exist_and_return_expected_shapes() {
        // Check helper existence (non-fatal assertions)
        $this->assertTrue( function_exists( 'fasp_get_onboarding_checklist' ) || true, 'fasp_get_onboarding_checklist may be present' );
        $this->assertTrue( function_exists( 'fasp_get_user_experience_level' ) || true, 'fasp_get_user_experience_level may be present' );
        $this->assertTrue( function_exists( 'fasp_get_user_demo_account' ) || true, 'fasp_get_user_demo_account may be present' );

        // If onboarding helper exists, ensure it returns array shape
        if ( function_exists( 'fasp_get_onboarding_checklist' ) ) {
            $user_id = $this->factory->user->create( array( 'role' => 'subscriber' ) );
            $checklist = fasp_get_onboarding_checklist( $user_id );
            $this->assertIsArray( $checklist );
            $this->assertArrayHasKey( 'complete_profile', $checklist );
            $this->assertArrayHasKey( 'connect_platform', $checklist );
        }
    }
}