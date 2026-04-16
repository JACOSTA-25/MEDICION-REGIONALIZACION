@props(['disabled' => false])

<input @disabled($disabled) {{ $attributes->merge(['class' => 'border-gray-300 focus:border-[#00a9ad] focus:ring-[#00a9ad] rounded-md shadow-sm']) }}>
