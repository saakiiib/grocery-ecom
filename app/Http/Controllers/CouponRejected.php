<?php

namespace App\Http\Controllers;

/** Thrown when a coupon fails at order time — becomes a 422, never a 500. */
class CouponRejected extends \RuntimeException {}
