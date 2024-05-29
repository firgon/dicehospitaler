    /*
     * Add a timer on an action button :
     * params:
     *  - buttonId : id of the action button
     *  - time : time before auto click
     *  - pref : 0 is disabled (auto-click), 1 if normal timer, 2 if no timer and show normal button
     */

    startActionTimer(buttonId, time, pref, autoclick = false) {
        var button = $(buttonId);
        var isReadOnly = this.isReadOnly();
        if (button == null || isReadOnly || pref == 2) {
          debug('Ignoring startActionTimer(' + buttonId + ')', 'readOnly=' + isReadOnly, 'prefValue=' + pref);
          return;
        }
  
        // If confirm disabled, click on button
        if (pref == 0) {
          if (autoclick) button.click();
          return;
        }
  
        this._actionTimerLabel = button.innerHTML;
        this._actionTimerSeconds = time;
        this._actionTimerFunction = () => {
          var button = $(buttonId);
          if (button == null) {
            this.stopActionTimer();
          } else if (this._actionTimerSeconds-- > 1) {
            button.innerHTML = this._actionTimerLabel + ' (' + this._actionTimerSeconds + ')';
          } else {
            debug('Timer ' + buttonId + ' execute');
            button.click();
          }
        };
        this._actionTimerFunction();
        this._actionTimerId = window.setInterval(this._actionTimerFunction, 1000);
        debug('Timer #' + this._actionTimerId + ' ' + buttonId + ' start');
      },
  
      stopActionTimer() {
        if (this._actionTimerId != null) {
          debug('Timer #' + this._actionTimerId + ' stop');
          window.clearInterval(this._actionTimerId);
          delete this._actionTimerId;
        }
      },