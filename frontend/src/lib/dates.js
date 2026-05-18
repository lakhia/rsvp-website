const fmt = new Intl.DateTimeFormat('en-US', {
  weekday: 'short',
  month: 'short',
  day: 'numeric',
});

export const fmtWeekday = new Intl.DateTimeFormat('en-US', { weekday: 'short' });
export const fmtMonthDay = new Intl.DateTimeFormat('en-US', { month: 'short', day: 'numeric' });

// "2025-04-06" → Date (avoids timezone offset issues)
export function parseYMD(str) {
  const [y, m, d] = str.split('-');
  return new Date(+y, m - 1, +d);
}

// "2025-04-06" → "Mon, Apr 6"
export function getDisplayDate(input) {
  if (!input || input.split('-').length < 3) return input;
  return fmt.format(parseYMD(input));
}
