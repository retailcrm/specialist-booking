# RFC: перенос настроек модуля в embed-ui страницы

## Статус

Актуальная спецификация реализации на 2026-04-22.

Документ фиксирует текущее целевое состояние проекта после переноса настроек в embed-ui страницы. Устаревший вариант с загрузкой фото файлом/base64 исключён: для фото специалиста используется ссылка на изображение.

## Контекст

До переноса js-модуль в `embed/` регистрировал только виджет карточки заказа `order/card:customer.after`, а настройки работали через классические Symfony/Twig страницы:

- `/settings/details` — настройки длительности слота и выбора филиала/города;
- `/settings/specialties` — список специализаций;
- `/settings/specialists` — список специалистов;
- `/settings` — основная служебная страница аккаунта;
- `/settings/for-developers` — раздел для разработчиков.

Операционные настройки переносятся в embed-ui pages. Основная `/settings` остаётся служебной страницей аккаунта. Раздел `/settings/for-developers` не переносится.

## Цель

В CRM регистрируется раздел `Запись к специалисту` в меню `private_main_menu` с ordering `200`.

Подразделы (в порядке меню):

- `Специалисты`;
- `Специализации`;
- `Настройки`.

Родительский пункт меню используется только как группировка. Отдельная runnable embed-ui страница для родителя не нужна, потому что родительские пункты меню в CRM не кликаются.

## Не входит

- Перенос страницы `Для разработчиков`.
- Перенос основной `/settings` с данными аккаунта, `clientId`, активностью и состоянием заморозки.
- Изменение бизнес-логики подбора слотов в виджете заказа.
- Multipart/file upload в embed-ui страницах.
- Миграции данных.

## Регистрация страниц

Описание страниц находится в `App\Service\EmbedStatic` и используется как единый источник:

- при регистрации кастомного модуля в `src/Controller/AccountController.php`;
- при генерации `manifest.json` для zip-архива через `src/Command/EmbedZipCommand.php`.

Коды страниц:

- `specialist-booking` — родительский пункт меню `Запись к специалисту`;
- `specialist-booking-settings` — `Настройки`;
- `specialist-booking-specialties` — `Специализации`;
- `specialist-booking-specialists` — `Специалисты`.

У всех page явно указывается `menu: private_main_menu`. У дочерних страниц указывается `parentMenuItemCode: page:specialist-booking`.

Ordering:

- root: `200`;
- specialists: `100`;
- specialties: `200`;
- settings: `300`.

Для API регистрации используются `RetailCrm\Api\Model\Entity\Integration\EmbedJs\EmbedJsPage` и `EmbedJsTranslation`.
Для zip-манифеста `App\Service\DTO\JsModuleManifest` содержит секцию `pages`, сериализованную из тех же `EmbedStatic::getPages()`.

## Frontend

Endpoint `embed/src/booking/index.ts` регистрирует:

- существующий widget через `defineWidgetRunner`;
- три страницы через `definePageRunner`.

Страницы:

- `embed/src/booking/pages/SettingsPage.vue`;
- `embed/src/booking/pages/SpecialtiesPage.vue`;
- `embed/src/booking/pages/SpecialistsPage.vue`.

API-клиент админских страниц находится в `embed/src/booking/api/adminApi.ts`.
Общие DTO-типы находятся в `embed/src/booking/types.ts`.

UI строится на публичных компонентах `@retailcrm/embed-ui-v1-components/remote`.
Страницы используют `useHost().httpCall('/embed/api/admin/...')`.

Локализация встроена в страницы для:

- `ru-RU`;
- `en-GB`;
- `es-ES`.

У страниц нет subtitle. Заголовки рисуются через `UiPageHeader`.
Ошибки выводятся через `UiError`.
Ошибки валидации с бэка приходят с `path` и выводятся у соответствующего поля, если поле найдено.

Таблицы:

- используют `UiTable`;
- имеют footer summary с количеством в формате `{{ rowsCount }} элементов` через i18n plural rules;
- значения первой колонки оформлены как `UiLink accent` и открывают ту же форму, что клик по строке.

Кнопки добавления:

- текст `Добавить`;
- иконка `add-circle-outlined.svg`.

Формы в `UiModalSidebar` завернуты в `<form @submit.prevent="submit">`, чтобы Enter сабмитил форму.

## Backend API

Публичные runtime-ручки виджета остаются в `EmbedApiController`.
Админские embed-ui ручки вынесены в отдельные контроллеры рядом с остальными контроллерами:

- `AdminApiController` — базовая обработка payload, аккаунта и ошибок валидации;
- `AdminSettingsController`;
- `AdminSpecialtyController`;
- `AdminSpecialistController`.

Все админские роуты находятся под `/embed/api/admin/*`.

Роуты:

- `GET|POST /embed/api/admin/settings` — получить или сохранить `slotDuration`, `chooseStore`, `chooseCity`;
- `GET|POST /embed/api/admin/specialties` — список специализаций;
- `POST /embed/api/admin/specialties/save` — сохранить одну специализацию;
- `POST /embed/api/admin/specialties/delete` — удалить специализацию;
- `GET|POST /embed/api/admin/specialists` — список специалистов, специализаций и филиалов;
- `POST /embed/api/admin/specialists/save` — сохранить одного специалиста;
- `POST /embed/api/admin/specialists/delete` — удалить специалиста;
- `POST /embed/api/admin/calendar` — сводка записей и свободных слотов за период (до 31 дня);
- `POST /embed/api/admin/calendar/preferences` — запомнить филиал календаря за пользователем CRM;
- `POST /embed/api/admin/customers` — поиск клиентов CRM по имени, телефону или email;
- `POST /embed/api/admin/book` — оформить запись менеджером: слот перепроверяется, занятый — 409 `slot_not_available`.

`GET|POST` на list/settings нужны из-за transport-особенностей `httpCall`; write-операции остаются `POST`.

Существующий `POST /embed/api/settings` остаётся для виджета записи.

## Payload и response DTO

Входные payload DTO находятся в `src/Controller/Payload`:

- `AdminSettingsPayload`;
- `SpecialtyPayload`;
- `SpecialistPayload`;
- `DeletePayload`.

Response DTO находятся в `src/Controller/Response`:

- `AdminSettingsResponse`, `AdminSettings`;
- `AdminSpecialtiesResponse`, `AdminSpecialty`;
- `AdminSpecialistsResponse`, `AdminSpecialist`, `AdminStore`;
- `ApiError`, `ApiFieldError`.

Контроллеры работают с DTO, а не с произвольными массивами после парсинга payload.

`AdminApiController::getPayload()` поддерживает:

- form поле `payload` с JSON-строкой, как приходит через embed `httpCall`;
- JSON body;
- обычные request fields.

Transport поле `clientId` удаляется из payload до создания DTO.

Ошибки валидации возвращаются в формате:

```json
{
  "error": "Validation failed.",
  "errors": [
    {
      "path": "photoUrl",
      "message": "This value is not a valid URL."
    }
  ]
}
```

Фронт сопоставляет `errors[].path` с полями формы.

## Сохранение текущей логики

Настройки:

- `slotDuration`: диапазон `15..360`;
- при сохранении `chooseStore = false` принудительно сохраняется `chooseCity = false`.

Специализации:

- список отсортирован по имени;
- создание и редактирование сохраняют одну запись через `/save`;
- удаление выполняется через `/delete`;
- при удалении у связанных специалистов сбрасывается специализация.

Специалисты:

- список отсортирован по `ordering`, затем `name`;
- колонки: имя с `UiAvatar`, специализация, филиал при `chooseStore = true`, сортировка;
- создание и редактирование сохраняют одну запись через `/save`;
- удаление выполняется через `/delete`;
- при включённом `chooseStore` филиалы берутся через `SpecialistBusySlotFetcherInterface::getStores()`;
- после save/delete вызывается `CustomFieldManager::ensureCustomFields($client, $specialists)`.

## Фото специалистов

В embed-ui страницах фото задаётся ссылкой на изображение, а не загрузкой файла.

Причина: endpoint работает как worker, а `vue-remote` сериализует DOM-события. Для `input[type=file]` в remote-дереве не передаётся реальный `File`/`Blob`, только сериализованные метаданные. В текущем публичном `HostApi` нет file picker/upload API.

Контракт save:

```json
{
  "photoUrl": "https://example.com/photo.png"
}
```

Поведение:

- пустое значение `photoUrl` сохраняет `null`, если поле было изменено;
- `removePhoto = true` удаляет фото;
- `photoUrl` валидируется как `http`/`https` URL, максимум `2048` символов;
- `removePhoto = true` вместе с непустым `photoUrl` считается ошибкой валидации;
- старые локальные имена файлов продолжают поддерживаться при чтении: они резолвятся через `ResolvableFilesystem`;
- внешние `http(s)` ссылки возвращаются в `photoUrl` как есть, без резолва через filesystem;
- публичная ручка `/embed/api/specialists` для виджета тоже возвращает внешнюю ссылку как есть.

Поддержка JSON/base64 upload удалена:

- нет `SpecialistPhotoPayload`;
- нет `photoPayload` во frontend DTO;
- нет `handlePhotoPayload()` в `AdminSpecialistController`;
- нет зависимости от `SluggerInterface` и `specialist_photos_filesystem` в админском save.

## Авторизация и clientId

Новые методы используют текущий механизм резолва аккаунта по `clientId`, как существующие `/embed/api/*`.

При отсутствии или неверном `clientId` возвращается `404`, чтобы поведение совпадало с существующим runtime API и тестами.

## Удаление классического UI

После переноса удаляются старые классические контроллеры, формы и Twig-страницы для операционных настроек:

- классические страницы настроек деталей, специализаций и специалистов;
- form model/type классы, которые использовались только этими Twig-страницами;
- Twig-шаблоны удалённых страниц.

`SettingsController::index()` и `templates/account/index.html.twig` остаются для `/settings`, но ссылки на удалённые классические страницы из шаблона убраны.

`AccountController` остаётся для регистрации, callback `config/register/activity/settings` и временного `for-developers`.

## Обратная совместимость

`accountUrl` при регистрации модуля остаётся `/settings`.

Основная навигация установленного модуля идёт через embed-ui pages.

Старые URL `/settings/details`, `/settings/specialties`, `/settings/specialists` после удаления контроллеров недоступны. Редиректы не добавляются.

Существующие локально сохранённые фото специалистов продолжают отображаться через `ResolvableFilesystem`, но новое фото из embed-ui задаётся только ссылкой.

## Тесты и проверки

Backend:

- `EmbedApiControllerTest` покрывает admin settings, specialties, specialists validation, неверный `clientId`, сохранение/чтение внешнего `photoUrl`;
- `EmbedStaticTest` проверяет наличие `pages` в manifest и `menu` у всех страниц;
- запускать `make php-cs-gitlab`;
- запускать `make phpstan`;
- запускать `docker compose run --rm --no-deps -e APP_ENV=test php bin/phpunit`.

Frontend:

- запускать `make js-lint`;
- запускать `make js-build`;
- вручную проверять страницы в CRM/embed-ui окружении.

Zip:

- `make zip-archive` должен положить в `manifest.json` секцию `pages`, идентичную `EmbedStatic::getPagesManifest()`.

## Риски

- В embed-ui нет публичного file upload API; при необходимости настоящей загрузки файла потребуется доработка host/embed-ui, а не только модуля.
- Внешняя ссылка на фото может перестать открываться на стороне пользователя или быть заблокирована политиками источника изображения.
- Удаление старых Twig-страниц может сломать пользовательские закладки на `/settings/details`, `/settings/specialties`, `/settings/specialists`; редиректы осознанно не добавляются.
- Happy-path save/delete специалистов вызывает `ensureCustomFields()` и может обращаться к RetailCRM API; тесты должны учитывать это ограничение или использовать моки окружения.

## Личное расписание специалиста

В шторке специалиста редактируются `workTimes` (день недели 1–7 → интервалы, формат хранения модуля)
и `nonWorkingDays` («мм.дд» без года). Пустое расписание — общий график компании из настроек CRM,
личные нерабочие дни складываются с общими праздниками. Строки формы «дни недели + интервал»
собираются по одинаковым интервалам, как расписания агента в CRM (`embed/src/booking/schedule.ts`).
Валидация — на клиенте и в `SpecialistPayload`.

## Календарь записей

Страница `specialist-booking-calendar` (`CalendarPage.vue`) живёт в меню продаж под «Заказами»
(`activity_main_menu` / `orders`), а не в настройках — это рабочий экран менеджера. Неделя с
переключением, фильтры по специализации и специалисту на клиенте, строка — специалист, столбец — день.
В ячейке записи из заказов CRM (клик открывает карточку заказа через `host.goTo('crm_orders_edit')`)
и число свободных слотов; клик по нему открывает шторку записи. Записи читает `OrderBookingReader`
одним запросом заказов по кодам специалистов и диапазону даты визита, свободные слоты считает
`SpecialistSchedule` — тот же расчёт, что у виджета и агента.

Шторка записи: время из свободных слотов, поиск клиента (`CrmCustomerSearch`, фильтр `name`
списка клиентов CRM), фамилия, имя, телефон, комментарий. Заказ создаёт `OrderBookingWriter`
с привязкой к найденному клиенту; сайт заказа — первый сайт аккаунта, как у виджета.

При включённом выборе филиала он в календаре обязателен и идёт первым (город, если включён, сужает
список филиалов). Выбранный филиал запоминается за пользователем CRM на сервере (`UserPreference`,
`POST /embed/api/admin/calendar/preferences`): у страницы в воркере своего хранилища нет.

## TODO

* В подвале шторок перегруппировать кнопки, как в шторках црм (например в задаче). Удаление иконкой со всплывашкой подтверждения
* Сделай фоны страниц по гайдлайнам (верх белый, дальше серый)
* Бокс формы + подвал с кнопками на странице настроек
* Добавить фильтры/пагинацию в таблицу специалистов с отражением в get-параметрах
