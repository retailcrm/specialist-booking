import { createI18n } from 'vue-i18n'
import {
    defineRunner,
    defineWidgetRunner,
    runEndpoint,
} from '@retailcrm/embed-ui-v1-endpoint/remote'

import BookingExtension from './BookingExtension.vue'

const createI18nInstance = () => createI18n({
    legacy: false,
    locale: 'ru-RU',
    fallbackLocale: 'en-GB',
    pluralRules: {
        'ru-RU': (choice, choicesLength) => {
            if (choice === 0) {
                return 0
            }

            const teen = choice > 10 && choice < 20
            const endsWithOne = choice % 10 === 1

            if (!teen && endsWithOne) {
                return 1
            }

            if (!teen && choice % 10 >= 2 && choice % 10 <= 4) {
                return 2
            }

            return choicesLength < 4 ? 2 : 3
        },
    },
})

runEndpoint(defineRunner({
    widgets: [{
        'order/card:customer.after': defineWidgetRunner(BookingExtension, app => {
            app.use(createI18nInstance())
        }),
    }],
    pages: [{}],
}))
