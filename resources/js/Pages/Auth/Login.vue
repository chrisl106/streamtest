<template>
  <div class="min-h-screen flex items-center justify-center bg-gradient-to-br from-slate-900 via-blue-900 to-slate-800">
    <div class="max-w-md w-full mx-4">
      <!-- Logo and Branding -->
      <div class="text-center mb-8">
        <div class="mb-4">
          <img
            src="/logo.png"
            alt="Rugby Try-Lights"
            class="w-20 h-20 mx-auto mb-4 rounded-full bg-white/10 p-2"
            onerror="this.style.display='none'"
          />
          <h1 class="text-3xl font-bold text-white mb-2">RTLVod</h1>
        </div>
        <p class="text-lg text-blue-200 mb-2">
          Access premium rugby content with your Patreon membership
        </p>
        <p class="text-sm text-blue-300/80">
          You must have an active paid Patreon membership to use RTLVod
        </p>
      </div>

      <!-- Login Card -->
      <div class="bg-white/10 backdrop-blur-lg rounded-xl p-8 shadow-2xl border border-white/20">
        <!-- Error Messages -->
        <div v-if="$page.props.flash.error" class="mb-6 p-4 bg-red-500/20 border border-red-500/30 rounded-lg">
          <p class="text-red-200 text-sm">{{ $page.props.flash.error }}</p>
        </div>

        <!-- Login Button -->
        <div class="text-center">
          <a
            :href="patreonUrl"
            class="inline-flex items-center px-8 py-4 bg-orange-500 hover:bg-orange-600 text-white font-semibold rounded-lg transition-colors duration-200 shadow-lg hover:shadow-xl transform hover:scale-105 transition-transform"
          >
            <span class="text-2xl mr-3">🔶</span>
            Login with Patreon
          </a>
        </div>

        <!-- Sign Up Link -->
        <div class="mt-6 text-center">
          <p class="text-blue-200 text-sm">
            Don't have a Patreon account?
            <a
              href="https://www.patreon.com"
              target="_blank"
              class="text-orange-400 hover:text-orange-300 font-medium underline"
            >
              Sign up on Patreon
            </a>
          </p>
        </div>

        <!-- Terms -->
        <div class="mt-6 text-center">
          <p class="text-blue-300/60 text-xs">
            By logging in with Patreon, you agree to share your membership status with RTLVod
          </p>
        </div>
      </div>

      <!-- Footer -->
      <div class="text-center mt-8">
        <p class="text-blue-300/60 text-xs">
          Powered by StreamTest Platform
        </p>
      </div>
    </div>
  </div>
</template>

<script setup>
defineProps({
  patreonUrl: String,
});
</script>

<style scoped>
/* Custom animations and effects */
@keyframes glow {
  0%, 100% {
    box-shadow: 0 0 20px rgba(249, 115, 22, 0.3);
  }
  50% {
    box-shadow: 0 0 30px rgba(249, 115, 22, 0.5);
  }
}

.bg-orange-500:hover {
  animation: glow 2s ease-in-out infinite;
}
</style>
