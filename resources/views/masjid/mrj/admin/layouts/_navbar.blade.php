<nav class="bg-white shadow px-4 py-3 sticky top-0 z-30">
  <div class="max-w-7xl mx-auto flex items-center justify-between">
    <div class="flex items-center gap-4">
      <button @click="$dispatch('toggle-sidebar')" class="md:hidden p-2 rounded-md hover:bg-gray-100">
        <i class="fas fa-bars"></i>
      </button>
      <div class="hidden sm:block">
        <form action="#" method="GET">
          <div class="relative">
            <input type="text" name="q" placeholder="Type here..." class="form-input pl-10 pr-4" />
            <span class="absolute left-3 top-1/2 -translate-y-1/2 text-gray-400"><i class="fas fa-search"></i></span>
          </div>
        </form>
      </div>
    </div>

    <div class="flex items-center gap-4">
      <div class="hidden sm:flex items-center gap-3">
        <a href="#" class="text-sm text-gray-600 flex items-center gap-2">
          <i class="fa fa-user"></i>
          <span>{{ Auth::user()->name ?? 'User' }}</span>
        </a>
      </div>

      <div class="sm:hidden">
        <button @click="$dispatch('toggle-mobile-menu')" class="p-2 rounded-md hover:bg-gray-100">
          <i class="fas fa-ellipsis-v"></i>
        </button>
      </div>
    </div>
  </div>
</nav>
