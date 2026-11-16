<?php

namespace Tests\Unit;

use App\Modules\FloatingContact\Support\FloatingContactLinks;
use Tests\TestCase;

class FloatingContactLinksTest extends TestCase
{
    public function test_tel_link_keeps_only_digits_and_leading_plus(): void
    {
        $this->assertSame('tel:02812345678', FloatingContactLinks::telLink('(028) 1234-5678'));
        $this->assertSame('tel:+84909123456', FloatingContactLinks::telLink('+84 909 123 456'));
    }

    public function test_tel_link_rejects_blank_or_non_numeric_values(): void
    {
        $this->assertNull(FloatingContactLinks::telLink(''));
        $this->assertNull(FloatingContactLinks::telLink('   '));
        $this->assertNull(FloatingContactLinks::telLink('abc'));
    }

    public function test_zalo_link_accepts_raw_phone_number_and_converts_it(): void
    {
        $this->assertSame('https://zalo.me/0909123456', FloatingContactLinks::zaloLink('0909123456'));
        $this->assertSame('https://zalo.me/84909123456', FloatingContactLinks::zaloLink('+84 909 123 456'));
    }

    public function test_zalo_link_accepts_full_url_with_numeric_phone_id(): void
    {
        $this->assertSame('https://zalo.me/0909123456', FloatingContactLinks::zaloLink('https://zalo.me/0909123456'));
    }

    public function test_zalo_link_accepts_full_url_with_alphanumeric_official_account_id(): void
    {
        $this->assertSame('https://zalo.me/oa1234abcd', FloatingContactLinks::zaloLink('https://zalo.me/oa1234abcd'));
    }

    public function test_zalo_link_rejects_other_domains_and_schemes(): void
    {
        $this->assertNull(FloatingContactLinks::zaloLink('https://zalo.me.evil.com/123'));
        $this->assertNull(FloatingContactLinks::zaloLink('https://evil.com/zalo.me/123'));
        $this->assertNull(FloatingContactLinks::zaloLink('http://zalo.me/0909123456'));
        $this->assertNull(FloatingContactLinks::zaloLink('https://zalo.me/abc?evil=1'));
        $this->assertNull(FloatingContactLinks::zaloLink(''));
    }

    public function test_messenger_link_accepts_valid_mme_url(): void
    {
        $this->assertSame('https://m.me/spa.abc', FloatingContactLinks::messengerLink('https://m.me/spa.abc'));
    }

    public function test_messenger_link_rejects_other_domains_and_schemes(): void
    {
        $this->assertNull(FloatingContactLinks::messengerLink('https://m.me.evil.com/spa.abc'));
        $this->assertNull(FloatingContactLinks::messengerLink('https://facebook.com/spa.abc'));
        $this->assertNull(FloatingContactLinks::messengerLink('javascript:alert(1)'));
        $this->assertNull(FloatingContactLinks::messengerLink(''));
    }
}
