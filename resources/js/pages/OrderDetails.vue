<template>
    <div class="min-h-screen bg-gray-50 dark:bg-gray-900">
        <Header title="Order Details" :cart-item-count="0" @cart-click="handleCartClick" />

        <main class="container mx-auto px-4 py-8">
            <div class="mx-auto max-w-5xl">
                <div class="mb-6 flex items-center justify-between">
                    <h1 class="text-3xl font-bold text-gray-900 dark:text-white">Order Details</h1>
                    <Link
                        :href="route('orders.index')"
                        class="rounded-lg border border-gray-300 px-4 py-2 text-sm font-medium text-gray-700 transition-colors duration-200 hover:bg-gray-100 dark:border-gray-600 dark:text-gray-300 dark:hover:bg-gray-800"
                    >
                        Back to Orders
                    </Link>
                </div>

                <div class="rounded-lg bg-white p-6 shadow-lg dark:bg-gray-800">
                    <div class="mb-6 flex items-start justify-between">
                        <div>
                            <h2 class="mb-2 text-xl font-semibold text-gray-900 dark:text-white">Order #{{ order.order_number }}</h2>
                            <p class="text-gray-600 dark:text-gray-400">Placed on {{ formatDate(order.created_at) }}</p>
                        </div>
                        <span class="inline-flex rounded-full px-3 py-1 text-xs font-semibold" :class="statusBadgeClasses(order.status)">
                            {{ formatStatus(order.status) }}
                        </span>
                    </div>

                    <div class="mb-6 grid grid-cols-1 gap-6 md:grid-cols-2">
                        <div>
                            <h3 class="mb-2 font-semibold text-gray-900 dark:text-white">Customer</h3>
                            <p class="text-gray-600 dark:text-gray-400">{{ order.shipping_name }}</p>
                            <p class="text-gray-600 dark:text-gray-400">{{ order.shipping_email }}</p>
                            <p v-if="order.shipping_phone" class="text-gray-600 dark:text-gray-400">{{ order.shipping_phone }}</p>
                        </div>
                        <div>
                            <h3 class="mb-2 font-semibold text-gray-900 dark:text-white">Shipping Address</h3>
                            <div class="text-gray-600 dark:text-gray-400">
                                <p>{{ order.shipping_name }}</p>
                                <p>{{ order.shipping_address }}</p>
                                <p v-if="order.shipping_address2">{{ order.shipping_address2 }}</p>
                                <p>{{ order.shipping_city }}</p>
                                <p>{{ order.shipping_country }}</p>
                            </div>
                        </div>
                    </div>

                    <div class="border-t border-gray-200 pt-6 dark:border-gray-700">
                        <h3 class="mb-4 font-semibold text-gray-900 dark:text-white">Items</h3>
                        <div class="space-y-4">
                            <div v-for="item in order.items" :key="item.id" class="flex items-center space-x-4">
                                <img :src="item.product?.image || '/placeholder-image.jpg'" :alt="item.product_name" class="h-16 w-16 rounded-lg object-cover" />
                                <div class="flex-1">
                                    <h4 class="font-medium text-gray-900 dark:text-white">{{ item.product_name }}</h4>
                                    <p class="text-sm text-gray-500 dark:text-gray-400">Qty: {{ item.quantity }}</p>
                                </div>
                                <div class="text-right">
                                    <p class="font-medium text-gray-900 dark:text-white">£{{ Number(item.price).toFixed(2) }}</p>
                                    <p class="text-sm text-gray-500 dark:text-gray-400">Total: £{{ Number(item.total).toFixed(2) }}</p>
                                </div>
                            </div>
                        </div>
                    </div>

                    <div class="mt-6 border-t border-gray-200 pt-6 dark:border-gray-700">
                        <div class="space-y-2">
                            <div class="flex justify-between">
                                <span class="text-gray-600 dark:text-gray-400">Subtotal</span>
                                <span class="text-gray-900 dark:text-white">£{{ Number(order.subtotal).toFixed(2) }}</span>
                            </div>
                            <div class="flex justify-between">
                                <span class="text-gray-600 dark:text-gray-400">Tax</span>
                                <span class="text-gray-900 dark:text-white">£{{ Number(order.tax).toFixed(2) }}</span>
                            </div>
                            <div class="flex justify-between">
                                <span class="text-gray-600 dark:text-gray-400">Shipping</span>
                                <span class="text-gray-900 dark:text-white">£{{ Number(order.shipping).toFixed(2) }}</span>
                            </div>
                            <div class="flex justify-between border-t border-gray-200 pt-2 text-lg font-semibold dark:border-gray-700">
                                <span class="text-gray-900 dark:text-white">Total</span>
                                <span class="text-gray-900 dark:text-white">£{{ Number(order.total).toFixed(2) }}</span>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </main>
    </div>
</template>

<script setup lang="ts">
import type { Order } from '@/types/product';
import { Link, router } from '@inertiajs/vue3';
import Header from '../components/Header.vue';

interface Props {
    order: Order;
}

defineProps<Props>();

const formatDate = (dateString: string) => {
    return new Date(dateString).toLocaleDateString('en-GB', {
        year: 'numeric',
        month: 'long',
        day: 'numeric',
        hour: '2-digit',
        minute: '2-digit',
    });
};

const formatStatus = (status: string) => {
    return status.charAt(0).toUpperCase() + status.slice(1);
};

const statusBadgeClasses = (status: string) => {
    // Order status badges: 'pending', 'confirmed', 'shipped', 'delivered'
    if (status === 'confirmed') {
        return 'bg-green-100 text-green-800 dark:bg-green-900/40 dark:text-green-300';
    }

    if (status === 'pending') {
        return 'bg-amber-100 text-amber-800 dark:bg-amber-900/40 dark:text-amber-300';
    }

    if (status === 'shipped' || status === 'delivered') {
        return 'bg-blue-100 text-blue-800 dark:bg-blue-900/40 dark:text-blue-300';
    }

    return 'bg-gray-100 text-gray-800 dark:bg-gray-700 dark:text-gray-300';
};

const handleCartClick = () => {
    router.visit('/cart');
};
</script>
