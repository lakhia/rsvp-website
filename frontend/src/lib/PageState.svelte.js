export class PageState {
  loading = $state(false);
  saving = $state(false);
  msg = $state('');
  msgType = $state('success');

  async load(fn) {
    this.loading = true;
    this.msg = '';
    this.msgType = 'success';
    try {
      return await fn();
    } catch (e) {
      this.msg = e.message || 'Request failed, try again';
      this.msgType = 'error';
    } finally {
      this.loading = false;
    }
  }

  async save(fn) {
    this.saving = true;
    this.msg = '';
    this.msgType = 'success';
    try {
      return await fn();
    } catch (e) {
      this.msg = e.message || 'Request failed, try again';
      this.msgType = 'error';
    } finally {
      this.saving = false;
    }
  }
}
