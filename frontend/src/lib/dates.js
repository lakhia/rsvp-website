const fmt = new Intl.DateTimeFormat('en-US', {
  weekday: 'short',
  month: 'short',
  day: 'numeric',
});

// "2025-04-06" → "Mon, Apr 6"
export function getDisplayDate(input) {
  const parts = input.split('-');
  if (parts.length < 3) return input;
  return fmt.format(new Date(parts[0], parts[1] - 1, parts[2]));
}
