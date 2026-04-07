const DAYS = ['Sun', 'Mon', 'Tue', 'Wed', 'Thr', 'Fri', 'Sat'];

// "2025-04-06" → "Sun, 04-06"
export function getDisplayDate(input) {
	const parts = input.split('-');
	if (parts.length < 3) return input;
	const d = new Date(parts[0], parts[1] - 1, parts[2]);
	const withoutYear = input.replace(/^\d+-/, '');
	return DAYS[d.getDay()] + ', ' + withoutYear;
}
