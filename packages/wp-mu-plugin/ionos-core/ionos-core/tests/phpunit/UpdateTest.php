<?php

namespace ionos\ionos_core;

/**
 * covers ionos\ionos_core\fetch_update_info()'s S3 resolution
 *
 * run only this test using 'pnpm test:php --php-opts "--filter UpdateTest"'
 *
 * @group ionos-core
 */
class UpdateTest extends \WP_UnitTestCase {
  public function tearDown(): void {
    \remove_all_filters('pre_http_request');

    parent::tearDown();
  }

  private function respond_by_url(array $responses_by_url): void {
    \add_filter('pre_http_request', function ($preempt, $parsed_args, $url) use ($responses_by_url) {
      return $responses_by_url[$url] ?? $preempt;
    }, 10, 3);
  }

  private static function json_response(array $body): array {
    return [
      'headers'  => [],
      'cookies'  => [],
      'filename' => null,
      'response' => ['code' => 200, 'message' => 'OK'],
      'body'     => json_encode($body),
    ];
  }

  public function test_s3_answering_with_valid_json_is_used(): void {
    $this->respond_by_url([
      INFO_JSON_URL => self::json_response(['version' => '9.9.9', 'package' => 'https://s3.example/pkg.zip']),
    ]);

    $info = fetch_update_info();

    $this->assertSame(['version' => '9.9.9', 'package' => 'https://s3.example/pkg.zip'], $info);
  }

  public function test_s3_non_200_status_returns_null(): void {
    $this->respond_by_url([
      INFO_JSON_URL => [
        'headers'  => [],
        'cookies'  => [],
        'filename' => null,
        'response' => ['code' => 500, 'message' => 'Internal Server Error'],
        'body'     => '',
      ],
    ]);

    $this->assertNull(fetch_update_info());
  }

  public function test_s3_malformed_json_returns_null(): void {
    $this->respond_by_url([
      INFO_JSON_URL => [
        'headers'  => [],
        'cookies'  => [],
        'filename' => null,
        'response' => ['code' => 200, 'message' => 'OK'],
        'body'     => '{not valid json',
      ],
    ]);

    $this->assertNull(fetch_update_info());
  }

  public function test_s3_missing_fields_returns_null(): void {
    $this->respond_by_url([
      INFO_JSON_URL => self::json_response(['version' => '9.9.9']),
    ]);

    $this->assertNull(fetch_update_info());
  }

  public function test_s3_empty_package_returns_null(): void {
    $this->respond_by_url([
      INFO_JSON_URL => self::json_response(['version' => '9.9.9', 'package' => '']),
    ]);

    $this->assertNull(fetch_update_info());
  }

  public function test_s3_request_failure_returns_null(): void {
    $this->respond_by_url([
      INFO_JSON_URL => new \WP_Error('http_request_failed', 'Connection timeout'),
    ]);

    $this->assertNull(fetch_update_info());
  }
}
