<script setup lang="ts">
import { computed, ref } from 'vue';

const props = defineProps<{
    modelValue: string | null;
}>();

const emit = defineEmits<{
    'update:modelValue': [date: string];
}>();

const calendarDate = ref(
    new Date(new Date().getFullYear(), new Date().getMonth(), 1),
);
const monthLabel = computed(() =>
    new Intl.DateTimeFormat('pt-BR', { month: 'long', year: 'numeric' }).format(
        calendarDate.value,
    ),
);
const calendarCells = computed(() => {
    const year = calendarDate.value.getFullYear();
    const month = calendarDate.value.getMonth();
    const firstDay = new Date(year, month, 1).getDay();
    const daysInMonth = new Date(year, month + 1, 0).getDate();

    return [
        ...Array.from({ length: firstDay }, (_, index) => ({
            key: `blank-${index}`,
            date: null,
            day: '',
        })),
        ...Array.from({ length: daysInMonth }, (_, index) => {
            const day = index + 1;
            const date = `${year}-${String(month + 1).padStart(2, '0')}-${String(day).padStart(2, '0')}`;

            return { key: date, date, day: String(day) };
        }),
    ];
});
const weekdays = ['D', 'S', 'T', 'Q', 'Q', 'S', 'S'];

function changeMonth(offset: number) {
    calendarDate.value = new Date(
        calendarDate.value.getFullYear(),
        calendarDate.value.getMonth() + offset,
        1,
    );
}
</script>

<template>
    <section class="calendar card" aria-label="Escolher data para retirada">
        <div class="calendar-head">
            <button
                type="button"
                aria-label="Mês anterior"
                @click="changeMonth(-1)"
            >
                ‹
            </button>
            <strong>{{ monthLabel }}</strong>
            <button
                type="button"
                aria-label="Próximo mês"
                @click="changeMonth(1)"
            >
                ›
            </button>
        </div>
        <div class="calendar-grid">
            <span
                v-for="(weekday, index) in weekdays"
                :key="index"
                class="weekday"
            >
                {{ weekday }}
            </span>
            <template v-for="cell in calendarCells" :key="cell.key">
                <span v-if="!cell.date" aria-hidden="true"></span>
                <button
                    v-else
                    type="button"
                    :class="{ selected: props.modelValue === cell.date }"
                    :aria-pressed="props.modelValue === cell.date"
                    @click="emit('update:modelValue', cell.date)"
                >
                    {{ cell.day }}
                </button>
            </template>
        </div>
        <p class="muted small">
            {{
                props.modelValue
                    ? `Data selecionada: ${props.modelValue}`
                    : 'Escolha uma data para retirar seu pedido.'
            }}
        </p>
    </section>
</template>
