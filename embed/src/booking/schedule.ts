// Личное расписание специалиста: в модуле хранится как «день недели → интервалы»
// (день 1 — понедельник, 7 — воскресенье), в форме редактируется строками
// «дни недели + один интервал», как расписания агента в CRM.
export type WorkTimes = Record<string, [string, string][]>
export type NonWorkingDays = [string, string][]

export type ScheduleRow = {
    key: number
    weekdays: number[]
    startTime: string | null
    endTime: string | null
}

export type ScheduleError =
    | 'requiredWeekdays'
    | 'requiredTime'
    | 'timeOrder'
    | 'overlap'

export const END_OF_DAY = '24:00'
export const DEFAULT_START_TIME = '09:00'
export const DEFAULT_END_TIME = '18:00'
export const WEEKDAYS = [1, 2, 3, 4, 5, 6, 7]

let rowKeySequence = 0

export const createRow = (weekdays: number[] = []): ScheduleRow => ({
    key: ++rowKeySequence,
    weekdays,
    startTime: DEFAULT_START_TIME,
    endTime: DEFAULT_END_TIME,
})

export const timeToMinutes = (time: string | null | undefined): number | null => {
    if (!time) return null

    if (time === END_OF_DAY) return 24 * 60

    const [hours, minutes] = time.split(':').map(Number)

    if (!Number.isFinite(hours) || !Number.isFinite(minutes)) return null

    return hours * 60 + minutes
}

// Таймпикер не принимает 24:00, поэтому конец суток в поле «до» показывается как 00:00
export const endTimeForPicker = (time: string | null): string | null => (
    time === END_OF_DAY ? '00:00' : time
)

export const endTimeFromPicker = (value: string | null): string | null => {
    if (!value) return null

    return value === '00:00' || value === END_OF_DAY ? END_OF_DAY : value
}

// Одинаковые интервалы разных дней собираются в одну строку, чтобы
// «пн–пт 09:00–18:00» показывалось одной строкой
export const rowsFromWorkTimes = (workTimes: WorkTimes | null): ScheduleRow[] => {
    if (!workTimes) return []

    const rows = new Map<string, ScheduleRow>()

    for (const [day, periods] of Object.entries(workTimes)) {
        const weekday = Number(day)

        for (const [startTime, endTime] of periods) {
            const signature = `${startTime}|${endTime}`
            const row = rows.get(signature)

            if (row) {
                if (!row.weekdays.includes(weekday)) row.weekdays.push(weekday)
                continue
            }

            rows.set(signature, { key: ++rowKeySequence, weekdays: [weekday], startTime, endTime })
        }
    }

    return [...rows.values()].map(row => ({
        ...row,
        weekdays: [...row.weekdays].sort((left, right) => left - right),
    }))
}

export const workTimesFromRows = (rows: ScheduleRow[]): WorkTimes | null => {
    const workTimes: WorkTimes = {}

    for (const row of rows) {
        for (const weekday of row.weekdays) {
            const periods = workTimes[String(weekday)] ?? []

            periods.push([row.startTime ?? DEFAULT_START_TIME, row.endTime ?? DEFAULT_END_TIME])
            workTimes[String(weekday)] = periods
        }
    }

    for (const periods of Object.values(workTimes)) {
        periods.sort((left, right) => (timeToMinutes(left[0]) ?? 0) - (timeToMinutes(right[0]) ?? 0))
    }

    return Object.keys(workTimes).length > 0 ? workTimes : null
}

export const validateRows = (rows: ScheduleRow[]): ScheduleError | null => {
    if (!rows.length) return 'requiredWeekdays'

    const windowsByWeekday = new Map<number, [number, number][]>()

    for (const row of rows) {
        const start = timeToMinutes(row.startTime)
        const end = timeToMinutes(row.endTime)

        if (!row.weekdays.length) return 'requiredWeekdays'

        if (start === null || end === null) return 'requiredTime'

        if (end <= start) return 'timeOrder'

        for (const weekday of row.weekdays) {
            const windows = windowsByWeekday.get(weekday) ?? []

            if (windows.some(([busyStart, busyEnd]) => start < busyEnd && busyStart < end)) return 'overlap'

            windows.push([start, end])
            windowsByWeekday.set(weekday, windows)
        }
    }

    return null
}

// Нерабочие дни хранятся как «мм.дд» без года, в форме вводятся как «дд.мм».
// 29 февраля допустимо: период повторяется ежегодно.
const DAYS_IN_MONTH = [31, 29, 31, 30, 31, 30, 31, 31, 30, 31, 30, 31]

export const parseDayMonth = (value: string): string | null => {
    const match = /^\s*(\d{1,2})\.(\d{1,2})\s*$/.exec(value)

    if (!match) return null

    const day = Number(match[1])
    const month = Number(match[2])

    if (month < 1 || month > 12 || day < 1 || day > DAYS_IN_MONTH[month - 1]) return null

    return `${String(month).padStart(2, '0')}.${String(day).padStart(2, '0')}`
}

export const formatMonthDay = (value: string): string => {
    const [month, day] = value.split('.')

    return day && month ? `${day}.${month}` : value
}

export const formatNonWorkingDay = ([from, to]: [string, string]): string => (
    from === to ? formatMonthDay(from) : `${formatMonthDay(from)} – ${formatMonthDay(to)}`
)
