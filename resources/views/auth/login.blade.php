@extends('frontend.layout')
@section('title', 'Login | OriginSpaces Modular UK')
@section('content')

  <!-- Page Hero -->
  <section class="pt-8 md:pt-10 pb-8 px-4 sm:px-6 lg:px-10 border-b border-[#e5e2da]">
    <div class="max-w-md mx-auto text-center">
      <span class="text-[11px] uppercase tracking-[0.3em] text-[#9a7b4f] font-mono font-semibold block mb-4">— Account Access</span>
      <h1 class="font-serif text-4xl sm:text-5xl text-[#1a1d24] font-semibold tracking-tight leading-[1.1]">Welcome Back</h1>
      <p class="mt-4 text-sm text-[#374151] leading-relaxed">Sign in to your account to continue.</p>
    </div>
  </section>

  <!-- Login Card -->
  <section class="py-8 md:py-12 px-4 sm:px-6 lg:px-10 bg-[#FAF9F5]">
    <div class="max-w-md mx-auto bg-white rounded-2xl border border-[#e5e2da] p-6 sm:p-8 shadow-xs">
      @if(session('error'))
        <div class="mb-4 p-3.5 rounded-xl bg-red-50 border border-red-200 text-sm text-red-700">{{ session('error') }}</div>
      @endif
      @if(session('status'))
        <div class="mb-4 p-3.5 rounded-xl bg-green-50 border border-green-200 text-sm text-green-700">{{ session('status') }}</div>
      @endif

      <form method="POST" action="{{ route('login') }}" class="space-y-4">
        @csrf
        @if(request()->has('redirect'))
          <input type="hidden" name="redirect" value="{{ request('redirect') }}">
        @endif

        <div>
          <label for="login" class="block text-xs font-mono uppercase text-[#6b7280] mb-1.5 font-semibold">Email or Phone</label>
          <div class="relative">
            <x-icon name="user" class="w-4 h-4 text-[#9a7b4f] absolute left-3.5 top-1/2 -translate-y-1/2 pointer-events-none" />
            <input id="login" type="text" name="login" value="{{ old('login') }}" placeholder="Email or 01XXXXXXXXX" required autofocus
              class="w-full border border-[#e5e2da] rounded-lg py-3 pl-10 pr-4 text-sm text-[#1a1d24] focus:border-[#9a7b4f] focus:outline-none placeholder:text-[#9ca3af]" />
          </div>
          @error('login')
            <p class="text-xs text-red-600 mt-1.5">{{ $message }}</p>
          @enderror
        </div>

        <div>
          <label for="password" class="block text-xs font-mono uppercase text-[#6b7280] mb-1.5 font-semibold">Password</label>
          <div class="relative">
            <x-icon name="lock" class="w-4 h-4 text-[#9a7b4f] absolute left-3.5 top-1/2 -translate-y-1/2 pointer-events-none" />
            <input id="password" type="password" name="password" placeholder="Enter your password" required
              class="w-full border border-[#e5e2da] rounded-lg py-3 pl-10 pr-12 text-sm text-[#1a1d24] focus:border-[#9a7b4f] focus:outline-none placeholder:text-[#9ca3af]" />
            <button type="button" onclick="togglePass()" aria-label="Show password"
              class="absolute right-3 top-1/2 -translate-y-1/2 text-[#6b7280] hover:text-[#9a7b4f] transition-colors">
              <span id="passShow"><x-icon name="eye" class="w-4 h-4" /></span>
              <span id="passHide" class="hidden"><x-icon name="eye-off" class="w-4 h-4" /></span>
            </button>
          </div>
          @error('password')
            <p class="text-xs text-red-600 mt-1.5">{{ $message }}</p>
          @enderror
        </div>

        <label class="flex items-center gap-2 text-sm text-[#6b7280] cursor-pointer">
          <input type="checkbox" name="remember" id="remember" {{ old('remember') ? 'checked' : '' }} class="w-4 h-4 accent-[#9a7b4f]" />
          <span>Remember me</span>
        </label>

        <button type="submit" class="w-full py-3.5 rounded-xl bg-[#181b20] hover:bg-[#9a7b4f] text-white text-xs font-semibold uppercase tracking-wider transition-all flex items-center justify-center gap-2">
          <x-icon name="arrow-right" class="w-4 h-4" />
          <span>Sign In</span>
        </button>
      </form>
    </div>
  </section>

@endsection
@section('script')
<script>
    function togglePass() {
      var input = document.getElementById('password');
      var show = document.getElementById('passShow');
      var hide = document.getElementById('passHide');
      if (!input) return;
      if (input.type === 'password') {
        input.type = 'text';
        if (show) show.classList.add('hidden');
        if (hide) hide.classList.remove('hidden');
      } else {
        input.type = 'password';
        if (show) show.classList.remove('hidden');
        if (hide) hide.classList.add('hidden');
      }
    }
    if (window.lucide) lucide.createIcons();
</script>
@endsection
