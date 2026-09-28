<?php

namespace ionos\essentials;

/**
 * covers ionos\essentials\fetch_update_info()'s S3-first / GitHub-fallback resolution
 *
 * run only this test using 'pnpm test:php --php-opts "--filter UpdateTest"'
 *
 * @group essentials
 */
class UpdateTest extends \WP_UnitTestCase {
  private const S3_URL = 'https://s3-de-central.profitbricks.com/web-hosting/test/ionos-essentials-info.json';

  public function setUp(): void {
    parent::setUp();

    \activate_plugin('ionos-essentials/ionos-essentials.php');
  }

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

  public function test_s3_answering_with_valid_json_is_used_and_github_is_not_queried(): void {
    $github_requested = false;

    \add_filter('pre_http_request', function ($preempt, $parsed_args, $url) use (&$github_requested) {
      if (LEGACY_INFO_JSON_URL === $url) {
        $github_requested = true;
      }

      return self::S3_URL === $url ? self::json_response(['version' => '9.9.9', 'package' => 'https://s3.example/pkg.zip']) : $preempt;
    }, 10, 3);

    $info = fetch_update_info(self::S3_URL);

    $this->assertSame(['version' => '9.9.9', 'package' => 'https://s3.example/pkg.zip'], $info);
    $this->assertFalse($github_requested, 'github must not be queried once s3 already answered');
  }

  public function test_s3_non_200_status_falls_back_to_github(): void {
    $this->respond_by_url([
      self::S3_URL => [
        'headers'  => [],
        'cookies'  => [],
        'filename' => null,
        'response' => ['code' => 500, 'message' => 'Internal Server Error'],
        'body'     => '',
      ],
      LEGACY_INFO_JSON_URL => self::json_response(['version' => '1.2.3', 'package' => 'https://github.example/pkg.zip']),
    ]);

    $info = fetch_update_info(self::S3_URL);

    $this->assertSame(['version' => '1.2.3', 'package' => 'https://github.example/pkg.zip'], $info);
  }

  public function test_s3_malformed_json_falls_back_to_github(): void {
    $this->respond_by_url([
      self::S3_URL => [
        'headers'  => [],
        'cookies'  => [],
        'filename' => null,
        'response' => ['code' => 200, 'message' => 'OK'],
        'body'     => '{not valid json',
      ],
      LEGACY_INFO_JSON_URL => self::json_response(['version' => '1.2.3', 'package' => 'https://github.example/pkg.zip']),
    ]);

    $info = fetch_update_info(self::S3_URL);

    $this->assertSame(['version' => '1.2.3', 'package' => 'https://github.example/pkg.zip'], $info);
  }

  public function test_s3_missing_fields_falls_back_to_github(): void {
    $this->respond_by_url([
      self::S3_URL         => self::json_response(['version' => '9.9.9']),
      LEGACY_INFO_JSON_URL => self::json_response(['version' => '1.2.3', 'package' => 'https://github.example/pkg.zip']),
    ]);

    $info = fetch_update_info(self::S3_URL);

    $this->assertSame(['version' => '1.2.3', 'package' => 'https://github.example/pkg.zip'], $info);
  }

  public function test_s3_empty_package_falls_back_to_github(): void {
    $this->respond_by_url([
      self::S3_URL         => self::json_response(['version' => '9.9.9', 'package' => '']),
      LEGACY_INFO_JSON_URL => self::json_response(['version' => '1.2.3', 'package' => 'https://github.example/pkg.zip']),
    ]);

    $info = fetch_update_info(self::S3_URL);

    $this->assertSame(['version' => '1.2.3', 'package' => 'https://github.example/pkg.zip'], $info);
  }

  public function test_both_sources_failing_returns_null(): void {
    $this->respond_by_url([
      self::S3_URL          => new \WP_Error('http_request_failed', 'Connection timeout'),
      LEGACY_INFO_JSON_URL  => new \WP_Error('http_request_failed', 'Connection timeout'),
    ]);

    $this->assertNull(fetch_update_info(self::S3_URL));
  }
}
