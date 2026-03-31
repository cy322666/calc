<div class="space-y-4">
    <h1 class="text-2xl font-bold">Калькулятор</h1>

    <div class="grid gap-6 lg:grid-cols-3">
        <div class="space-y-4 lg:col-span-2">
            <div class="rounded-xl border p-4">
                <label class="mb-1 block text-sm font-medium">Тип</label>
                <select wire:model.change="data.type_rolled_curtains" class="w-full rounded-lg border px-3 py-2">
                    @foreach($systems as $system)
                        <option value="{{ $system['code'] }}">{{ $system['name'] }}</option>
                    @endforeach
                </select>
            </div>

            <div class="rounded-xl border p-4">
                <label class="mb-1 block text-sm font-medium">Тип цены</label>
                <select wire:model="data.rol_price_tier" class="w-full rounded-lg border px-3 py-2">
                    <option value="opt">ОПТ</option>
                    <option value="opt1">ОПТ 1</option>
                    <option value="opt2">ОПТ 2</option>
                    <option value="opt3">ОПТ 3</option>
                    <option value="opt4">ОПТ 4</option>
                    <option value="vip">ВИП</option>
                </select>
            </div>

            <div class="rounded-xl border p-4">
                <label class="mb-1 block text-sm font-medium">Дата расчета (курс ЦБ)</label>
                <input type="date" wire:model="data.calculation_date" class="w-full rounded-lg border px-3 py-2">
            </div>

            <div>
                <button
                    type="button"
                    wire:click="calculateAction"
                    wire:loading.attr="disabled"
                    wire:target="calculateAction"
                    class="inline-flex items-center rounded-lg border border-orange-600 bg-orange-500 px-5 py-2 font-semibold text-black"
                >
                    <span wire:loading.remove wire:target="calculateAction">Рассчитать</span>
                    <span wire:loading wire:target="calculateAction">Считаю...</span>
                </button>
            </div>

            <div class="rounded-xl border p-4 space-y-3">
                @forelse($components as $component)
                    @php
                        $componentId = $component['id'];
                        $variants = $component['variants'] ?? [];
                    @endphp

                    <div class="grid items-start gap-3 md:grid-cols-2" wire:key="component-row-{{ $componentId }}">
                        <label class="flex items-center gap-2">
                            <input type="checkbox" wire:model.live="data.components_qty.{{ $componentId }}">
                            <span>{{ $component['name'] }}</span>
                        </label>

                        <div>
                            @if(count($variants) > 1)
                                <select
                                    wire:key="component-variant-{{ $componentId }}"
                                    wire:model.live="data.components_variant.{{ $componentId }}"
                                    class="w-full rounded-lg border px-3 py-2"
                                >
                                    <option value="">Вариант</option>
                                    @foreach($variants as $variant)
                                        <option value="{{ $variant['id'] }}">{{ $variant['name'] }}</option>
                                    @endforeach
                                </select>
                            @endif
                        </div>
                    </div>
                @empty
                    <p class="text-sm text-gray-500">Нет комплектующих для выбранной системы.</p>
                @endforelse
            </div>

        </div>

        <div class="self-start rounded-xl border p-4 space-y-3 lg:sticky lg:top-4">
            <div>
                <label class="mb-1 block text-sm font-medium">Курс USD</label>
                <input
                    type="text"
                    class="w-full rounded-lg border px-3 py-2 bg-gray-50"
                    readonly
                    value="{{ $usdRate > 0 ? number_format($usdRate, 4, '.', '') : 0 }}"
                >
            </div>

            <div>
                <label class="mb-1 block text-sm font-medium">Моя цена</label>
                <input type="text" class="w-full rounded-lg border px-3 py-2 bg-gray-50" readonly value="{{ $myPrice }}">
            </div>

            <div>
                <label class="mb-1 block text-sm font-medium">Розничная цена</label>
                <input type="text" class="w-full rounded-lg border px-3 py-2 bg-gray-50" readonly value="{{ $retailPrice }}">
            </div>

            <div>
                <label class="mb-1 block text-sm font-medium">Разбивка</label>
                <textarea
                    class="w-full rounded-lg border px-3 py-2 bg-gray-50 font-mono text-sm leading-5 whitespace-pre-wrap"
                    style="min-height: 34rem; height: 75vh;"
                    readonly
                >{{ $priceBreakdown }}</textarea>
            </div>
        </div>
    </div>
</div>
