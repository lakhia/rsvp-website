export function getIntParam(searchParams, name, fallback = 0) {
	return parseInt(searchParams.get(name)) || fallback;
}
