<?php

namespace App\Http\Controllers;

/** Thrown when a loyalty balance changes before checkout commits. */
class PointsRejected extends \RuntimeException {}
