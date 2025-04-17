import './assets/main.css'

import { createApp, ref } from 'vue'
import PrimeVue from 'primevue/config'
import App from './App.vue'
import router from './router'
import 'bootstrap-icons/font/bootstrap-icons.css';
import { definePreset } from '@primevue/themes';
import Aura from '@primevue/themes/aura';
import { ConfirmationService, ToastService, Tooltip } from "primevue";
import GoogleLogin from 'vue3-google-login'
import ConfirmDialog from "primevue/confirmdialog"
import Toast from 'primevue/toast'

const ggClientID = import.meta.env.VITE_GOOGLE_CLIENT_ID;

const app = createApp(App)


// preset changes to aura theme
const customPreset = definePreset(Aura, {
    semantic: {
        primary: {
            50: '{sky.50}',
            100: '{sky.100}',
            200: '{sky.200}',
            300: '{sky.300}',
            400: '{sky.400}',
            500: '{sky.500}',
            600: '{sky.600}',
            700: '{sky.700}',
            800: '{sky.800}',
            900: '{sky.900}',
            950: '{sky.950}'
        },
        colorScheme: {
            dark: {
                primary: {
                    color: '{sky.900}',
                    inverseColor: '{zinc.950}',
                    hoverColor: '{zinc.100}',
                    activeColor: '{zinc.200}'
                },
                highlight: {
                    background: 'rgba(250, 250, 250, .16)',
                    focusBackground: 'rgba(250, 250, 250, .24)',
                    color: 'rgba(255,255,255,.87)',
                    focusColor: 'rgba(255,255,255,.87)'
                },
                surface: {
                    0: '#ffffff',
                    50: '{slate.50}',
                    100: '{slate.100}',
                    200: '{slate.200}',
                    300: '{slate.300}',
                    400: '{slate.400}',
                    500: '{slate.500}',
                    600: '{slate.600}',
                    700: '{slate.700}',
                    800: '{slate.800}',
                    900: '{slate.900}',
                    950: '{slate.950}'
                }
            }
        }
    },
    components: {
        menubar: {
            base: {
              item: {
                  padding: '19px',
                  borderRadius: '0',
                  background: '{red.500}',
              }
            },
            item: {
                active: {
                    background: '{sky.700}',
                    color: '{slate.50}'
                },
                focus: {
                    background: '{sky.700}',
                    color: '{slate.50}'
                },
            },
            padding: '0',
            background: '{sky.500}',
            borderRadius: '0px',
            borderColor: '{sky.500}',
            colorScheme: {
                dark: {
                    background: '{sky.900}',
                    borderColor: '{sky.900}',
                    borderWidth: '0px'
                }
            }
        },
        card: {
            borderRadius: '0px',
            shadow: '1px 1px 5px {slate.400}',
            colorScheme: {
                dark: {
                    shadow: 0
                }
            }
        },
        button: {
            colorScheme: {
                dark: {
                    primary: {
                        background: '{sky.800}',
                        color: '{white}'
                    }
                }
            }
        },
        toggleswitch: {
            colorScheme: {
                dark: {
                    checked: {
                        background: '{zinc.200}'
                    }
                }
            }
        }

    }
})

app.use(router)
app.use(PrimeVue, {
    theme:{
        preset: customPreset,
        options: {
            darkModeSelector: '.toggleTheme',
        }
    }
})
app.use(GoogleLogin, {
    clientId: '66107300806-ut64q3vdqqh0krb2jkgdvdv3ng694d4r.apps.googleusercontent.com'
});

app.use(ConfirmationService)
app.use(ToastService)
app.directive('tooltip', Tooltip)
app.component("ConfirmDialog", ConfirmDialog)
// eslint-disable-next-line vue/multi-word-component-names
app.component("Toast", Toast)
app.mount('#app')