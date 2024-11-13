<?php

class LoginTest extends WP_UnitTestCase {
  const TEST_USER_NAME = 'test-admin';

  private $myTestUser;

  public function setUp(): void {
    parent::setUp();

    $this->myTestUser = \WP_UnitTestCase_Base::factory()->user->create([
      'role' => 'administrator',
      'user_login' => self::TEST_USER_NAME,
    ]);
  }

  function test_login_admin(): void {
    $this->assertFalse(is_user_logged_in());

    $this->assertFalse(is_admin());

    \wp_set_current_user('admin');
    $this->assertTrue(is_user_logged_in());

    $this->assertSameSets(['administrator'], \wp_get_current_user()->roles);

    \wp_logout();
    $this->assertFalse(is_user_logged_in());
  }

  function test_login_myTestUser(): void {
    \wp_set_current_user($this->myTestUser);
    $this->assertEquals(self::TEST_USER_NAME, \wp_get_current_user()->user_login);

    $current_user = \wp_get_current_user();
    $this->assertSameSets(['administrator'], $current_user->roles);
  }
}
