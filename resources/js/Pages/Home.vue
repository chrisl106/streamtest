<template>
  <div class="min-h-screen bg-[var(--background)]">
    <!-- Navigation -->
    <nav class="bg-[var(--card)] border-b border-[var(--border)] sticky top-0 z-50">
      <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
        <div class="flex justify-between items-center h-16">
          <!-- Logo -->
          <div class="flex items-center">
            <h1 class="text-xl font-bold text-[var(--text)]">RTLVod</h1>
          </div>

          <!-- Search -->
          <div class="flex-1 max-w-lg mx-8">
            <div class="relative">
              <input
                v-model="searchQuery"
                @keyup.enter="performSearch"
                type="text"
                placeholder="Search videos..."
                class="w-full pl-10 pr-4 py-2 bg-[var(--background)] border border-[var(--border)] rounded-lg text-[var(--text)] placeholder-gray-400 focus:outline-none focus:ring-2 focus:ring-[var(--main)]"
              >
              <SearchIcon class="absolute left-3 top-2.5 h-5 w-5 text-gray-400" />
            </div>
          </div>

          <!-- User Menu -->
          <div class="flex items-center space-x-4">
            <span class="text-sm text-[var(--text)]">
              Welcome, {{ $page.props.auth.user.name }}
            </span>
            <Link
              href="/logout"
              method="post"
              class="text-sm text-gray-400 hover:text-[var(--text)]"
            >
              Logout
            </Link>
          </div>
        </div>
      </div>
    </nav>

    <!-- Main Content -->
    <main class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-8">
      <!-- Continue Watching -->
      <section v-if="continueWatching.length > 0" class="mb-12">
        <h2 class="text-2xl font-bold text-[var(--text)] mb-6">Continue Watching</h2>
        <div class="grid grid-cols-2 md:grid-cols-3 lg:grid-cols-6 gap-4">
          <div
            v-for="video in continueWatching"
            :key="video.id"
            class="bg-[var(--card)] rounded-lg overflow-hidden shadow-lg hover:shadow-xl transition-shadow cursor-pointer"
            @click="$inertia.visit(`/videos/${video.id}`)"
          >
            <div class="relative">
              <img
                :src="video.thumbnail"
                :alt="video.title"
                class="w-full h-32 object-cover"
              >
              <!-- Progress Bar -->
              <div class="absolute bottom-0 left-0 right-0 h-1 bg-gray-600">
                <div
                  class="h-full bg-[var(--main)]"
                  :style="{ width: video.progress + '%' }"
                ></div>
              </div>
            </div>
            <div class="p-3">
              <h3 class="font-medium text-[var(--text)] text-sm line-clamp-2">{{ video.title }}</h3>
            </div>
          </div>
        </div>
      </section>

      <!-- Categories -->
      <section v-for="category in categories" :key="category.id" class="mb-12">
        <div class="flex items-center justify-between mb-6">
          <h2 class="text-2xl font-bold text-[var(--text)]">{{ category.name }}</h2>
          <Link
            :href="`/categories/${category.id}`"
            class="text-[var(--main)] hover:text-[var(--accent)] text-sm font-medium"
          >
            View All →
          </Link>
        </div>

        <div class="grid grid-cols-2 md:grid-cols-3 lg:grid-cols-6 gap-4">
          <div
            v-for="video in category.videos"
            :key="video.id"
            class="bg-[var(--card)] rounded-lg overflow-hidden shadow-lg hover:shadow-xl transition-shadow cursor-pointer"
            @click="$inertia.visit(`/videos/${video.id}`)"
          >
            <img
              :src="video.thumbnail"
              :alt="video.title"
              class="w-full h-32 object-cover"
            >
            <div class="p-3">
              <h3 class="font-medium text-[var(--text)] text-sm line-clamp-2">{{ video.title }}</h3>
              <p class="text-xs text-gray-400 mt-1">{{ video.formatted_duration }}</p>
            </div>
          </div>
        </div>
      </section>

      <!-- Featured Videos -->
      <section v-if="featuredVideos.length > 0" class="mb-12">
        <h2 class="text-2xl font-bold text-[var(--text)] mb-6">Featured</h2>
        <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6">
          <div
            v-for="video in featuredVideos"
            :key="video.id"
            class="bg-[var(--card)] rounded-lg overflow-hidden shadow-lg hover:shadow-xl transition-shadow cursor-pointer"
            @click="$inertia.visit(`/videos/${video.id}`)"
          >
            <img
              :src="video.thumbnail"
              :alt="video.title"
              class="w-full h-48 object-cover"
            >
            <div class="p-4">
              <h3 class="font-semibold text-[var(--text)] text-lg line-clamp-2">{{ video.title }}</h3>
              <p class="text-sm text-gray-400 mt-2 line-clamp-3">{{ video.description }}</p>
              <div class="flex items-center justify-between mt-3">
                <span class="text-xs text-gray-400">{{ video.formatted_duration }}</span>
                <span class="text-xs text-gray-400">{{ video.views_count }} views</span>
              </div>
            </div>
          </div>
        </div>
      </section>
    </main>
  </div>
</template>

<script setup>
import { Link } from '@inertiajs/vue3';
import { SearchIcon } from 'lucide-vue-next';

defineProps({
  categories: Array,
  continueWatching: Array,
  featuredVideos: Array,
});

const searchQuery = ref('');

const performSearch = () => {
  if (searchQuery.value.trim()) {
    $inertia.visit(`/search?q=${encodeURIComponent(searchQuery.value)}`);
  }
};
</script>

<style scoped>
.line-clamp-2 {
  display: -webkit-box;
  -webkit-line-clamp: 2;
  -webkit-box-orient: vertical;
  overflow: hidden;
}

.line-clamp-3 {
  display: -webkit-box;
  -webkit-line-clamp: 3;
  -webkit-box-orient: vertical;
  overflow: hidden;
}
</style>
