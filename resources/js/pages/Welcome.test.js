/**
 * @vitest-environment jsdom
 */

import { mount } from '@vue/test-utils';
import { describe, expect, it, vi } from 'vitest';
import Welcome from './Welcome.vue';

// Mock @inertiajs/vue3 components
vi.mock('@inertiajs/vue3', () => ({
    Head: {
        name: 'Head',
        template: '<div><slot /></div>',
    },
    Link: {
        name: 'Link',
        props: ['href'],
        template: '<a :href="href"><slot /></a>',
    },
}));

// Mock route() helper
global.route = (name) => `/${name}`;

// Mock Inertia's $page global
const globalProperties = {
    $page: {
        props: {
            auth: {
                user: null, // change to {} to simulate logged in
            },
        },
    },
    route: (name) => `/${name}`,
};

describe('Welcome Page', () => {
    it('shows login/register when no user', () => {
        const wrapper = mount(Welcome, {
            global: {
                config: {
                    globalProperties,
                },
            },
        });

        expect(wrapper.text()).toContain('Log in');
        expect(wrapper.text()).toContain('Register');
        expect(wrapper.text()).not.toContain('Projects');
    });

    it('shows about card', () => {
        const wrapper = mount(Welcome, {
            global: {
                config: {
                    globalProperties,
                },
            },
        });
        expect(wrapper.text()).toContain('This monitoring service allows users to create projects and define multiple monitors');
    });

    it('shows Projects link when user is logged in', () => {
        const wrapper = mount(Welcome, {
            global: {
                config: {
                    globalProperties: {
                        $page: {
                            props: {
                                auth: {
                                    user: { id: 1, name: 'Test User' },
                                },
                            },
                        },
                        route: (name) => `/${name}`,
                    },
                },
            },
        });

        expect(wrapper.text()).toContain('Projects');
        expect(wrapper.text()).not.toContain('Log in');
    });
});

// ------------------------------------------------------------------------------------------------
//
// TIPS
//
// - console.log(wrapper.html()) can be used to inspect the rendered HTML
