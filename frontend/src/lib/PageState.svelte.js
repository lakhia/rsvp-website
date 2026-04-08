export class PageState {
	loading = $state(false);
	saving  = $state(false);
	msg     = $state('');

	async load(fn) {
		this.loading = true;
		this.msg = '';
		try {
			return await fn();
		} catch (e) {
			this.msg = e.message || 'Request failed, try again';
		} finally {
			this.loading = false;
		}
	}

	async save(fn) {
		this.saving = true;
		this.msg = '';
		try {
			return await fn();
		} catch (e) {
			this.msg = e.message || 'Request failed, try again';
		} finally {
			this.saving = false;
		}
	}
}
