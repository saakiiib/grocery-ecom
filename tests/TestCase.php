<?php

namespace Tests;

use App\Models\CompanyDetails;
use App\Models\Favourite;
use App\Models\OrderStatusHistory;
use App\Models\Setting;
use Illuminate\Foundation\Testing\TestCase as BaseTestCase;

abstract class TestCase extends BaseTestCase
{
    /**
     * RefreshDatabase rolls back the database between tests, but in-process
     * static memos survive in the same PHP process and would leak gateway
     * keys and cached rows into the next test. Flush them every time.
     */
    protected function setUp(): void
    {
        parent::setUp();

        Setting::flushMemo();
        CompanyDetails::flushMemo();
        Favourite::flushCache();
        OrderStatusHistory::flushCache();
    }

    protected function tearDown(): void
    {
        Setting::flushMemo();
        CompanyDetails::flushMemo();
        Favourite::flushCache();
        OrderStatusHistory::flushCache();

        parent::tearDown();
    }
}
