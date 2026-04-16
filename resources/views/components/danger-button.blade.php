<button {{ $attributes->merge(['type' => 'submit', 'class' => 'inline-flex items-center px-4 py-2 bg-[#00a9ad] border border-transparent rounded-md font-semibold text-xs text-white uppercase tracking-widest hover:bg-[#008d90] active:bg-[#00777a] focus:outline-none focus:ring-2 focus:ring-[#00a9ad] focus:ring-offset-2 transition ease-in-out duration-150']) }}>
    {{ $slot }}
</button>
