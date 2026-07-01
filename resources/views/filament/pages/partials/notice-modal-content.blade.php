<!-- Notice Popup Modal Content -->
<div class="pt-10">

    <h2 class="text-center text-3xl font-bold text-slate-800">
        গুরুত্বপূর্ণ নোটিশ
    </h2>

    <div class="my-7 flex items-center justify-center gap-3">
        <div class="h-0.5 w-16 rounded bg-amber-400"></div>
        <div class="h-2 w-2 rounded-full bg-amber-500"></div>
        <div class="h-0.5 w-16 rounded bg-amber-400"></div>
    </div>

    <!-- Bell Icon -->
    <div class="absolute left-1/2 -translate-x-1/2 -top-14">
        <div class="flex h-28 w-28 items-center justify-center rounded-full bg-white shadow-xl">
            <div class="flex h-24 w-24 items-center justify-center rounded-full bg-gradient-to-br from-amber-100 to-yellow-50">
                <svg xmlns="http://www.w3.org/2000/svg"
                     viewBox="0 0 64 64"
                     class="h-12 w-12 text-amber-500"
                     fill="currentColor">
                    <!-- Bell -->
                    <path d="M32 10c-7 0-12 5-12 12v7c0 3-1 6-3 8l-3 3h36l-3-3c-2-2-3-5-3-8v-7c0-7-5-12-12-12z"/>
                    <circle cx="32" cy="52" r="4"/>
                    <!-- Ring -->
                    <path d="M12 18c-2 2-3 5-3 8" fill="none" stroke="currentColor" stroke-width="3"/>
                    <path d="M52 18c2 2 3 5 3 8" fill="none" stroke="currentColor" stroke-width="3"/>
                </svg>
            </div>
        </div>
    </div>

    <!-- Rate Notice -->
    <div class="rounded-3xl border border-amber-200 bg-gradient-to-r from-amber-50 to-yellow-50 p-5">
        <div class="flex gap-4">
            <!-- Dollar Icon -->
            <div class="shrink-0">
                <div class="flex h-16 w-16 items-center justify-center rounded-full bg-amber-100">
                    <svg xmlns="http://www.w3.org/2000/svg"
                         viewBox="0 0 64 64"
                         class="h-9 w-9 text-amber-600"
                         fill="none"
                         stroke="currentColor"
                         stroke-width="3">
                        <circle cx="32" cy="32" r="24"/>
                        <path d="M32 18v28"/>
                        <path d="M38 24c0-3-3-5-6-5s-6 2-6 5 2 5 6 6 6 2 6 5-3 5-6 5-6-2-6-5"/>
                        <path d="M13 32a19 19 0 0 1 5-13"/>
                        <path d="M51 32a19 19 0 0 1-5 13"/>
                        <polyline points="15,17 18,19 19,15"/>
                        <polyline points="49,47 46,45 45,49"/>
                    </svg>
                </div>
            </div>
            <div class="text-md leading-7 text-slate-700 font-semibold">
                ডলারের রেট পরিবর্তনশীল।<br>
                তাই পেমেন্ট করার আগে অবশ্যই
                <span class="font-bold text-orange-600">
                    সর্বশেষ রেট
                </span>
                দেখে নিন।
            </div>
        </div>
    </div>

    <!-- Time Notice -->
    <div class="mt-5 rounded-3xl border border-emerald-200 bg-gradient-to-r from-emerald-50 to-teal-50 p-5">
        <div class="flex gap-4">
            <div class="shrink-0">
                <div class="flex h-16 w-16 items-center justify-center rounded-full bg-emerald-100">
                    <svg xmlns="http://www.w3.org/2000/svg"
                         viewBox="0 0 64 64"
                         fill="none"
                         stroke="currentColor"
                         stroke-width="3"
                         class="h-9 w-9 text-emerald-600">
                        <circle cx="32" cy="32" r="24"/>
                        <path d="M32 20v13"/>
                        <path d="M32 33l9 6"/>
                    </svg>
                </div>
            </div>
            <div class="text-md leading-7 text-slate-700 font-semibold">
                <span class="font-bold text-emerald-700">
                    সকাল ১০:০০টা
                </span>
                থেকে
                <span class="font-bold text-emerald-700">
                    রাত ১০:০০টা
                </span>
                পর্যন্ত করা পেমেন্ট দ্রুত অনুমোদন করা হবে।
            </div>
        </div>
    </div>

    <!-- Button -->
    <button
        type="submit"
        class="mt-8 flex h-14 w-full items-center justify-center gap-3 rounded-2xl bg-emerald-600 font-semibold text-white transition hover:bg-emerald-700 focus:outline-none">
        <!-- Check -->
        <svg xmlns="http://www.w3.org/2000/svg"
             viewBox="0 0 64 64"
             class="h-6 w-6"
             fill="none"
             stroke="currentColor"
             stroke-width="4">
            <circle cx="32" cy="32" r="26"/>
            <path d="M20 33l8 8 16-18"/>
        </svg>
        ঠিক আছে
    </button>

    <button class="mt-5 w-full text-center text-sm text-slate-400">
        ধন্যবাদ
    </button>

</div>
