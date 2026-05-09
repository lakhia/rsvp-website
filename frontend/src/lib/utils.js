export function getIntParam(searchParams, name, fallback = 0) {
  return parseInt(searchParams.get(name)) || fallback;
}

export function dateToOffset(dateStr) {
  const todayStr = new Date().toLocaleDateString('en-CA');
  const today = new Date(todayStr + 'T00:00:00');
  const target = new Date(dateStr + 'T00:00:00');
  return Math.round((target - today) / 86400000);
}

export function paginateUrl(basePath, dateParam, offset, delta) {
  if (dateParam) {
    const d = new Date(dateParam + 'T00:00:00');
    d.setDate(d.getDate() + delta);
    return `${basePath}?date=${d.toISOString().split('T')[0]}`;
  }
  return `${basePath}?offset=${offset + delta}`;
}
