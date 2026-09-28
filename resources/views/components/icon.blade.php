@props(['name' => ''])
@php
  // Single source of truth: paths are parsed from the project's icons.js once per request.
  static $iconPaths = null;
  if ($iconPaths === null) {
    $iconPaths = [];
    $js = @file_get_contents(public_path('resources/frontend-raw/js/icons.js'));
    if ($js && preg_match_all('/"([a-z0-9-]+)":\s*"((?:[^"\\\\]|\\\\.)*)"/', $js, $m)) {
      foreach ($m[1] as $i => $key) {
        $iconPaths[$key] = stripcslashes($m[2][$i]);
      }
    }
  }
  $inner = $iconPaths[$name] ?? '';
@endphp
@if($inner !== '')
<svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" {{ $attributes->merge(['class' => 'lucide lucide-'.$name]) }}>{!! $inner !!}</svg>
@endif
