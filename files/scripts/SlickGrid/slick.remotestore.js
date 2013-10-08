(function ($) {

  function RemoteStore(options) {
    // return;
    
    var _url =  window.location.pathname
    var _defualt_options = {pagesize : 100, url : _url + 'tree/', updateUrl: _url + 'update/', usePager: false};
    var options = $.extend(true, {}, _defualt_options, options);
    var pagesize = null;
    var dataView = new Slick.Data.DataView();
    var data = {total: 0, stories: []};
    var baseUrl = null;
    var updateUrl = null;
    var _fromPage = 0;
    var _toPage = 0;
    var currentPage = 0;
    var usePager = null;
    var sortcol = null;
    var sortdir = 1;
    var searchdata = null;
    var req = null; // ajax request

    setOptions(options);
    
    var onDataLoading = new Slick.Event();
    var onDataLoaded = new Slick.Event();
    var onPageInfoChanged = new Slick.Event();

    function setOptions(opts) {
      options = $.extend(true, {}, options, opts);
      pagesize = options.pagesize;
      baseUrl = options.url;
      updateUrl = options.updateUrl;
      usePager = options.usePager;

      if (usePager) {
        dataView.setPagingOptions({pageSize: pagesize});
      }
    }

    function init() {
    }

    function getFilteredItems() {
      return dataView.getFilteredAndPagedItems(dataView.getItems()).rows;
    }

    function getFilteredIndex(i) {
      var fdata = getFilteredItems();
      if (fdata[i]) {
        return dataView.getIdxById(fdata[i].id);
      }
      return data.stories.length > 0 ? data.stories.length + 1 : i;
    }

    function isDataLoaded(from, to) {
      if (from < 0) from = 0;
      for (var i = from; i <= to; i++) {
        if (data.stories[i] == undefined || data.stories[i] == null) {
          return false;
        }
      }
      return true;
    }

    function updateDataView() {
      dataView.beginUpdate();
      dataView.setItems(data.stories);
      dataView.endUpdate();
      var n = getPageNum();
      if (usePager) {
        dataView.setPagingOptions({pageNum: n});
        onPageInfoChanged.notify(getPagingInfo());
      }
    }

    function clear() {
      data.stories = [];
      data.total = 0;
      updateDataView();
    }

    function buildUrl(fromPage, toPage) {
      var url = baseUrl + (window.location.search ? window.location.search + "&" : "?")
        + "offset=" + (fromPage * pagesize) + "&count=" + (((toPage - fromPage) * pagesize) + pagesize);
      if (sortcol) {
        url += "&sortcol=" + sortcol + "&sortdir=" + sortdir;
      }
      if (searchdata) {
        var query = "";
        for (k in searchdata) {
          query += "&" + k + "=" + searchdata[k];
        }
        url += query;
      }
      return url;
    }


    function ensureData(from, to) {
      var len = to - from + 1;
      from = getFilteredIndex(from);
      to = getFilteredIndex(to) + len;
      // console.log("from to", from, to);
      // if (!to) {
      //   to = from + len;
      // }

      if (isDataLoaded(from, to)) {
        return;
      }
      if (req) {
        return;
        // req.abort();
        // for (var i = req.fromPage; i <= req.toPage; i++)
        //   data.stories[i * pagesize] = undefined;
      }
      if (from < 0) {
        from = 0;
      }

      var fromPage = Math.floor(from / pagesize);
      var toPage = Math.floor(to / pagesize);

      while (data.stories[fromPage * pagesize] !== undefined && fromPage < toPage)
        fromPage++;

      while (data.stories[toPage * pagesize] !== undefined && fromPage < toPage)
        toPage--;

      if (fromPage > toPage || ((fromPage == toPage) && data.stories[fromPage * pagesize] !== undefined)) {
        // TODO:  look-ahead
        return;
      }

      var url = buildUrl(fromPage, toPage);

      onDataLoading.notify({from: from, to: to});

      req = $.ajax({
        url: url,
        dataType: 'json',
        type: 'POST',
        data: JSON.stringify({last: data.stories.slice(-1)[0]}),
        success: onSuccess,
        error: function () {
          onError(fromPage, toPage)
        }
      });
      _fromPage = req.fromPage = fromPage;
      _toPage = req.toPage = toPage;

    }

    function updateItems(items, columns) {
      var sendData = [];
      if (typeof columns == 'undefined') {
        sendData = items;
      } else {
        for (var i in items) {
          item = {};
          for (var c in columns) {
            var name = columns[c];
            item[name] = items[i][name];
          }
          if (!item.id) {
            item.id = items[i].id;
          }
          sendData.push(item);
        }
      }

      $.ajax({
        url: updateUrl,
        type: 'POST',
        data: JSON.stringify(sendData),
        dataType: 'json',
        success: function(data) {
          // console.log(data);
        },
        error: function(data) {
          // console.log(data);
        }
      });

    }

    function onError(fromPage, toPage) {
      alert("error loading pages " + fromPage + " to " + toPage);
    }

    function onSuccess(resp) {
      var from = req.fromPage * pagesize, to = from + resp.count;
      var count = 0;
      for (i in resp.stories) {
        var item = resp.stories[i];
        if (typeof item.id != 'undefined') {
          data.stories.push(resp.stories[i]);
          // data.stories[parseInt(i)] = resp.stories[i];
          count++;
        }
      }
      data.total = resp.total;
      req = null;
      updateDataView();
      onDataLoaded.notify({from: from, to: to});
    }


    function reloadData(from, to) {
      for (var i = from; i <= to; i++) {
        delete data.stories[i];
      }
      ensureData(from, to);
    }


    function setSort(column, dir) {
      sortcol = column;
      sortdir = dir;
      clear();
    }

    function setSearch(data) {
      searchdata = data;
      clear();
    }

    function getOptions() {
      return options;
    }

    function getView() {
      return dataView;
    }

    function getPageSize() {
      return pagesize;
    }

    function setPageSize(ps) {
      pagesize = ps;
      dataView.setPagingOptions({pageSize: ps});
      var n = getPageNum();
      setPageNum(n);
      // onPageInfoChanged.notify(getPagingInfo());
      // dataView.setPagingOptions({pageNum: n});
      // ????
    }

    function getTotal() {
      return data.total;
    }

    function getPageNum() {
      return currentPage;
    }

    function setPageNum(n) {
      currentPage = n;
      var from = n * pagesize , to = from + pagesize;
      if (isDataLoaded(from, to)) {
        dataView.setPagingOptions({pageNum: n});
      } else {
        ensureData(from, to);
      }
      onPageInfoChanged.notify(getPagingInfo());
    }

    function getPagingInfo() {
      var totalPages = pagesize ? Math.max(1, Math.ceil(data.total / pagesize)) : 1;
      return {pageSize: pagesize, pageNum: getPageNum(), totalRows: getTotal(), totalPages: totalPages};
    }

    function getUrl() {
      return _url;
    }


    init();

    return {
      // properties
      "data": data,

      // methods
      "clear": clear,
      "isDataLoaded": isDataLoaded,
      "ensureData": ensureData,
      "reloadData": reloadData,
      "setSort": setSort,
      "setSearch": setSearch,
      "getView" : getView,
      "getOptions": getOptions,
      "updateItems": updateItems,
      "getPageSize" : getPageSize,
      "getTotal" : getTotal,
      "getPageNum" : getPageNum,
      "getPagingInfo" : getPagingInfo,
      "setPageSize" : setPageSize,
      "setPageNum" : setPageNum,
      "getFilteredItems" : getFilteredItems,
      "getUrl" : getUrl,
      "setOptions" : setOptions,

      // events
      "onDataLoading": onDataLoading,
      "onDataLoaded": onDataLoaded,
      "onPageInfoChanged" : onPageInfoChanged
    };
  }

  // Slick.Data.RemoteStore
  $.extend(true, window, { Slick: { Data: { RemoteStore: RemoteStore }}});
})(jQuery);