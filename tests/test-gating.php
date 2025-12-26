<?php
class FASP_Gating_Test extends WP_UnitTestCase {
    public function test_gating_options_structure() {
        $opt = fasp_get_gating_options();
        $this->assertIsArray( $opt );
        $this->assertArrayHasKey( 'roles', $opt );
        $this->assertArrayHasKey( 'require_login', $opt );
        $this->assertArrayHasKey( 'blocked_message', $opt );
        $this->assertArrayHasKey( 'blocked_redirect', $opt );
    }

    public function test_is_user_allowed_by_gating_requires_login() {
        update_option( 'fasp_gating_require_login', 1 );
        $res = fasp_is_user_allowed_by_gating( 0, null );
        $this->assertFalse( $res['allowed'] );
        update_option( 'fasp_gating_require_login', 0 );
    }

    public function test_is_user_allowed_by_gating_roles() {
        update_option( 'fasp_gating_roles', array( 'subscriber' ) );
        $user_id = $this->factory->user->create( array( 'role' => 'subscriber' ) );
        $res = fasp_is_user_allowed_by_gating( $user_id, null );
        $this->assertTrue( $res['allowed'] );
        $other_id = $this->factory->user->create( array( 'role' => 'editor' ) );
        $res2 = fasp_is_user_allowed_by_gating( $other_id, null );
        $this->assertFalse( $res2['allowed'] );
        update_option( 'fasp_gating_roles', array() );
    }
}
