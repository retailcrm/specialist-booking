# Specialist Booking Module

![Demo](demo.gif)

Интеграционный модуль к RetailCRM / Simla.com, позволяющий завести специалистов (врачи, парикмахеры и др)
и записывать клиента на прием к специалисту на определенный временной слот.

Специалист и дата-время записи фиксируются в заказе в пользовательские поля типа `dictionary` и `datetime` соответственно.

При записи клиента на прием отображаются свободные слоты:
1. Исключая слоты, занятые записями из других заказов
2. Настройки расписания рабочих дней и рабочего времени из настроек системы
3. Настройки нерабочих дней из настроек системы

## Структура проекта

* `src/` — исходный код бекенда (PHP/Symfony)
* `embed/` — исходный код js-модуля для встраивания в систему

## Install

1. Создайте `.env` из `.env.dist`, укажите значения для ENV-переменных `DATABASE_URL` и `AWS_*`
2. Создайте `auth.json` на основе `auth.json.dist` и внесите свой токен доступа
3. Выполните
```shell
make node-modules
make js-build
make vendor
make db-create
make start
```

## Development

Запуск линтеров и тестов
```shell
make check
APP_ENV=test make db-create db-migrate
APP_ENV=test make phpunit
make twig-lint
make yaml-lint
make js-lint
```

## Создание архива js-модуля

```shell
make zip-archive
```

В ходе выполнения будет запрошен номер версии модуля, который будет подставлен в manifest-файл.
