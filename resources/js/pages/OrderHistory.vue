<template>
    <div class="min-h-screen bg-gray-50 dark:bg-gray-900">
        <Header title="Order History" :cart-item-count="0" @cart-click="handleCartClick" />

        <main class="container mx-auto px-4 py-8">
            <div class="mx-auto max-w-5xl">
                <div class="mb-8 flex items-center justify-between">
                    <div>
                        <h1 class="text-3xl font-bold text-gray-900 dark:text-white">My Orders</h1>
                        <p class="mt-2 text-gray-600 dark:text-gray-400">Review your previous purchases and track current order status.</p>
                    </div>
                    <Link
                        :href="route('home')"
                        class="rounded-lg border border-gray-300 px-4 py-2 text-sm font-medium text-gray-700 transition-colors duration-200 hover:bg-gray-100 dark:border-gray-600 dark:text-gray-300 dark:hover:bg-gray-800"
                    >
                        Continue Shopping
                    </Link>
                </div>

                <div class="overflow-hidden rounded-lg bg-white shadow dark:bg-gray-800">
                    <div v-if="orders.length === 0" class="px-6 py-16 text-center">
                        <h2 class="text-xl font-semibold text-gray-900 dark:text-white">No orders yet</h2>
                        <p class="mt-2 text-gray-600 dark:text-gray-400">Once you complete checkout, your orders will appear here.</p>
                    </div>

                    <ul v-else class="divide-y divide-gray-200 dark:divide-gray-700">
                        <li v-for="order in orders" :key="order.id" class="p-6 transition-colors duration-200 hover:bg-gray-50 dark:hover:bg-gray-700/40">
                            <Link :href="route('orders.show', order.order_number)" class="grid grid-cols-1 gap-4 sm:grid-cols-2 lg:grid-cols-5">
                                <div>
                                    <p class="text-sm text-gray-500 dark:text-gray-400">Order</p>
                                    <p class="font-semibold text-gray-900 dark:text-white">{{ order.order_number }}</p>
                                </div>
                                <div>
                                    <p class="text-sm text-gray-500 dark:text-gray-400">Date</p>
                                    <p class="font-medium text-gray-900 dark:text-white">{{ formatDate(order.created_at) }}</p>
                                </div>
                                <div>
                                    <p class="text-sm text-gray-500 dark:text-gray-400">Total</p>
                                    <p class="font-medium text-gray-900 dark:text-white">£{{ Number(order.total).toFixed(2) }}</p>
                                </div>
                                <div>
                                    <p class="text-sm text-gray-500 dark:text-gray-400">Status</p>
                                    <span class="inline-flex rounded-full px-3 py-1 text-xs font-semibold" :class="statusBadgeClasses(order.status)">
                                        {{ formatStatus(order.status) }}
                                    </span>
                                </div>
                                <div class="flex items-end sm:items-center lg:justify-end">
                                    <span class="text-sm font-medium text-blue-600 dark:text-blue-400">View details</span>
                                </div>
                            </Link>
                        </li>
                    </ul>
                </div>

                <div v-if="pagination.last_page > 1" class="mt-8">
                    <Pagination :current-page="pagination.current_page" :total-pages="pagination.last_page" @page-change="handlePageChange" />
                </div>

            </div>
        </main>

    </div>
</template>

<script setup lang="ts">
import { Link, router } from '@inertiajs/vue3';
import Header from '../components/Header.vue';
import Pagination from '../components/Pagination.vue';

interface OrderSummary {
    id: number;
    order_number: string;
    total: number | string;
    status: string;
    created_at: string;
}

interface PaginationData {
    current_page: number;
    last_page: number;
    per_page: number;
    total: number;
}

interface Props {
    orders: OrderSummary[];
    pagination: PaginationData;
}

const props = defineProps<Props>();

const formatDate = (dateString: string) => {
    return new Date(dateString).toLocaleDateString('en-GB', {
        year: 'numeric',
        month: 'short',
        day: 'numeric',
    });
};

const formatStatus = (status: string) => {
    return status.charAt(0).toUpperCase() + status.slice(1);
};

const statusBadgeClasses = (status: string) => {
    // Order status badge: 'pending', 'confirmed', 'shipped', 'delivered'
    if (status === 'confirmed') {
        return 'bg-green-100 text-green-800 dark:bg-green-900/40 dark:text-green-300';
    }

    if (status === 'pending') {
        return 'bg-amber-100 text-amber-800 dark:bg-amber-900/40 dark:text-amber-300';
    }

    if (status === 'shipped' || status === 'delivered') {
        return 'bg-blue-100 text-blue-800 dark:bg-blue-900/40 dark:text-blue-300';
    }

    if (status === 'canceled') {
        return 'bg-red-100 text-red-800 dark:bg-red-900/40 dark:text-red-300';
    }

    return 'bg-gray-100 text-gray-800 dark:bg-gray-700 dark:text-gray-300';
};

const handlePageChange = (page: number) => {
    router.get(
        route('orders.index'),
        { page },
        {
            preserveState: true,
            replace: true,
        },
    );
};

const handleCartClick = () => {
    router.visit('/cart');
};
</script>
