<script setup lang="ts">
import { Link } from '@inertiajs/vue3';
import { ChevronRight } from 'lucide-vue-next';
import { ref } from 'vue';
import { Collapsible, CollapsibleContent } from '@/components/ui/collapsible';
import {
    SidebarGroup,
    SidebarGroupLabel,
    SidebarMenu,
    SidebarMenuButton,
    SidebarMenuItem,
    SidebarMenuSub,
    SidebarMenuSubButton,
    SidebarMenuSubItem,
} from '@/components/ui/sidebar';
import { useCurrentUrl } from '@/composables/useCurrentUrl';
import type { NavGroupItem } from '@/types';

const props = withDefaults(
    defineProps<{
        group: NavGroupItem;
        defaultOpen?: boolean;
    }>(),
    {
        defaultOpen: false,
    },
);

const { isCurrentOrParentUrl } = useCurrentUrl();

const isOpen = ref(
    props.defaultOpen ||
        props.group.children.some((child) => isCurrentOrParentUrl(child.href)),
);
</script>

<template>
    <SidebarGroup class="px-2 py-0">
        <SidebarGroupLabel>{{ group.title }}</SidebarGroupLabel>
        <SidebarMenu>
            <SidebarMenuItem>
                <Collapsible v-model:open="isOpen" class="group/collapsible">
                    <SidebarMenuButton
                        :tooltip="group.title"
                        @click="isOpen = !isOpen"
                    >
                        <component :is="group.icon" v-if="group.icon" />
                        <span>{{ group.title }}</span>
                        <ChevronRight
                            class="ml-auto transition-transform duration-200 group-data-[state=open]/collapsible:rotate-90"
                        />
                    </SidebarMenuButton>
                    <CollapsibleContent>
                        <SidebarMenuSub>
                            <SidebarMenuSubItem
                                v-for="child in group.children"
                                :key="child.title"
                            >
                                <SidebarMenuSubButton
                                    as-child
                                    :is-active="
                                        isCurrentOrParentUrl(child.href)
                                    "
                                >
                                    <Link :href="child.href">
                                        <component
                                            :is="child.icon"
                                            v-if="child.icon"
                                        />
                                        <span>{{ child.title }}</span>
                                    </Link>
                                </SidebarMenuSubButton>
                            </SidebarMenuSubItem>
                        </SidebarMenuSub>
                    </CollapsibleContent>
                </Collapsible>
            </SidebarMenuItem>
        </SidebarMenu>
    </SidebarGroup>
</template>
