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
    
    public function test_user_segmentation_novice() {
        if (!function_exists('fasp_get_user_segment')) {
            $this->markTestSkipped('fasp_get_user_segment function not available');
        }
        $user_id = $this->factory->user->create( array( 'role' => 'subscriber' ) );
        $segment = fasp_get_user_segment( $user_id );
        $this->assertEquals( 'novice', $segment );
    }
    
    public function test_user_segmentation_affiliate() {
        if (!function_exists('fasp_get_user_segment')) {
            $this->markTestSkipped('fasp_get_user_segment function not available');
        }
        $user_id = $this->factory->user->create( array( 'role' => 'affiliate' ) );
        $segment = fasp_get_user_segment( $user_id );
        $this->assertEquals( 'affiliate', $segment );
    }
    
    public function test_user_progress_calculation() {
        if (!function_exists('fasp_get_user_progress')) {
            $this->markTestSkipped('fasp_get_user_progress function not available');
        }
        $user_id = $this->factory->user->create( array( 'role' => 'subscriber' ) );
        $progress = fasp_get_user_progress( $user_id );
        $this->assertIsInt( $progress );
        $this->assertGreaterThanOrEqual( 0, $progress );
        $this->assertLessThanOrEqual( 100, $progress );
    }
    
    public function test_onboarding_checklist_structure() {
        if (!function_exists('fasp_get_onboarding_checklist')) {
            $this->markTestSkipped('fasp_get_onboarding_checklist function not available');
        }
        $user_id = $this->factory->user->create( array( 'role' => 'subscriber' ) );
        $checklist = fasp_get_onboarding_checklist( $user_id );
        $this->assertIsArray( $checklist );
        if (!empty($checklist)) {
            $this->assertArrayHasKey( 'id', $checklist[0] );
            $this->assertArrayHasKey( 'label', $checklist[0] );
            $this->assertArrayHasKey( 'completed', $checklist[0] );
        }
    }
    
    public function test_demo_trade_stats_structure() {
        if (!function_exists('fasp_get_demo_trade_stats')) {
            $this->markTestSkipped('fasp_get_demo_trade_stats function not available');
        }
        $user_id = $this->factory->user->create( array( 'role' => 'subscriber' ) );
        $stats = fasp_get_demo_trade_stats( $user_id );
        $this->assertIsArray( $stats );
        $this->assertArrayHasKey( 'total_trades', $stats );
        $this->assertArrayHasKey( 'win_rate', $stats );
    }
    
    public function test_live_trade_stats_structure() {
        if (!function_exists('fasp_get_live_trade_stats')) {
            $this->markTestSkipped('fasp_get_live_trade_stats function not available');
        }
        $user_id = $this->factory->user->create( array( 'role' => 'subscriber' ) );
        $stats = fasp_get_live_trade_stats( $user_id );
        $this->assertIsArray( $stats );
        $this->assertArrayHasKey( 'total_trades', $stats );
        $this->assertArrayHasKey( 'win_rate', $stats );
    }
}
