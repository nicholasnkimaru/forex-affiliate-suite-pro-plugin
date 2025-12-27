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
    
    /**
     * Test that dashboard helper functions exist and work
     */
    public function test_dashboard_helpers_exist() {
        $this->assertTrue( function_exists( 'fasp_is_affiliate' ) );
        $this->assertTrue( function_exists( 'fasp_get_user_experience_level' ) );
        $this->assertTrue( function_exists( 'fasp_get_onboarding_checklist' ) );
    }
    
    /**
     * Test affiliate detection
     */
    public function test_affiliate_detection() {
        // Test with admin user
        $admin_id = $this->factory->user->create( array( 'role' => 'administrator' ) );
        $this->assertTrue( fasp_is_affiliate( $admin_id ) );
        
        // Test with regular subscriber
        $user_id = $this->factory->user->create( array( 'role' => 'subscriber' ) );
        $this->assertFalse( fasp_is_affiliate( $user_id ) );
        
        // Test with usermeta flag
        update_user_meta( $user_id, 'fasp_is_affiliate', true );
        $this->assertTrue( fasp_is_affiliate( $user_id ) );
    }
    
    /**
     * Test experience level detection
     */
    public function test_experience_level_detection() {
        $user_id = $this->factory->user->create( array( 'role' => 'subscriber' ) );
        
        // New user should be novice
        $level = fasp_get_user_experience_level( $user_id );
        $this->assertEquals( 'novice', $level );
        
        // Set explicit level
        update_user_meta( $user_id, 'fasp_experience_level', 'experienced' );
        $level = fasp_get_user_experience_level( $user_id );
        $this->assertEquals( 'experienced', $level );
    }
    
    /**
     * Test onboarding checklist structure
     */
    public function test_onboarding_checklist() {
        $user_id = $this->factory->user->create( array( 'role' => 'subscriber' ) );
        $checklist = fasp_get_onboarding_checklist( $user_id );
        
        $this->assertIsArray( $checklist );
        $this->assertArrayHasKey( 'complete_profile', $checklist );
        $this->assertArrayHasKey( 'verify_email', $checklist );
        $this->assertArrayHasKey( 'connect_platform', $checklist );
        $this->assertArrayHasKey( 'complete_tutorial', $checklist );
        $this->assertArrayHasKey( 'make_first_trade', $checklist );
    }
}
