<?= $this->include('template/v_header') ?>
<?= $this->include('template/v_appbar') ?>

<div class="main-content content margin-t-4">
    <div class="card-header d-flex align-items-center" style="gap:10px;">
        <div class="card-header d-flex" style="gap:30px;">
            <!-- Filter tanggal -->
            <div class="d-flex flex-column" style="width:150px;">
                <label for="dateFrom" class="form-label mb-0" style="font-size:0.75rem;">From Date</label>
                <input type="date" id="dateFrom" name="dateFrom"
                    class="form-control form-control-sm rounded" style="width:110px;">
            </div>

            <div class="d-flex flex-column" style="width:150px;">
                <label for="dateTo" class="form-label mb-0" style="font-size:0.75rem;">To Date</label>
                <input type="date" id="dateTo" name="dateTo"
                    class="form-control form-control-sm rounded" style="width:110px;">
            </div>

            <div class="form-floating" style="width:150px;">
                <label for="customerid" style="font-size:0.75rem;">Customer</label>
                <select id="customerid" name="customerid"
                    class="form-select form-select-sm rounded">
                </select>
            </div>

            <div class="d-flex align-items-end ms-auto gap-2">
                <button type="button" id="btnFilter" class="btn btn-info btn-sm">
                    <i class="bx bx-search"></i> Filter
                </button>
                <button type="button" id="btnReset" class="btn btn-secondary btn-sm">
                    <i class="bx bx-reset"></i> Reset
                </button>
            </div>
        </div>

        <!-- Action buttons -->
        <div class="d-flex align-items-end ms-auto gap-2">
            <button type="button" id="btnAddNew" class="btn btn-primary btn-sm"
                onclick="window.location.href='<?= site_url('salesorder/form') ?>'">
                <i class="bx bx-plus-circle"></i> Add New
            </button>
            <button type="button" id="btnExport" class="btn btn-success btn-sm btnExport">
                <i class="bx bx-download"></i> Export
            </button>
            <button type="button" class="btn btn-warning btn-sm"
                onclick="return modalForm('Import Sales Order', 'modal-lg', '<?= getURL('salesorder/formImport') ?>')">
                <i class="bx bx-upload margin-r-2"></i>
                <span class="fw-normal fs-7">Import</span>
            </button>
        </div>
    </div>
    <div class="card-body">
        <div class="table-responsive margin-t-14p">
            <table class="table table-bordered table-responsive-lg table-master fs-7 w-100" id="dataTable">
                <thead>
                    <tr>
                        <td class="tableheader">No</td>
                        <td class="tableheader">Transcode</td>
                        <td class="tableheader">Transdate</td>
                        <td class="tableheader">Customer Name</td>
                        <td class="tableheader">Grand Total</td>
                        <td class="tableheader">Description</td>
                        <td calss="tableheader">Created Date</td>
                        <td class="tableheader">Created By</td>
                        <td class="tableheader">Actions</td>
                    </tr>
                </thead>
                <tbody>
                </tbody>
            </table>
        </div>
    </div>
</div>

<?= $this->include('template/v_footer') ?>
<form id="exportexcel" style="padding-inline:0px;">
    <div class="modal fade" id="modalExport" tabindex="-1" role="dialog" aria-hidden="true">
        <div class="modal-dialog modal-sm modal-dialog-centered" role="document">
            <div class="modal-content p-3">
                <div class="modal-header">
                    <h5 class="modal-title">Export Progress</h5>
                </div>
                <div class="modal-body text-center">
                    <i class='bx bx-loader-circle bx-spin text-info fs-1'></i>
                    <h5 class="mt-3">
                        Processing <span class="text-primary" id="progressPercent">0%</span>
                    </h5>
                </div>
                <hr>
                <div class="modal-footer d-flex justify-content-center">
                    <button class="btn btn-warning w-100" type="button" id="btnCancel">Cancel</button>
                </div>
            </div>
        </div>
    </div>
</form>
<script>
    let cancelExport = false;
    let currentRequest = null;

    $(document).ready(function() {
        // Init DataTable
        initDataTable();

        // Init Select2 Customer
        $('#customerid').select2({
            placeholder: '-- Select Name--',
            allowClear: true,
            width: '170px',
            ajax: {
                url: '<?= base_url("salesorder/customer/list") ?>',
                type: 'POST',
                dataType: 'json',
                delay: 250,
                data: params => ({
                    search: params.term
                }),
                processResults: data => ({
                    results: data.items
                })
            }
        });

        $('#btnFilter').on('click', function() {
            initDataTable();
        });

        $('#btnReset').on('click', function() {
            $('#dateFrom').val('');
            $('#dateTo').val('');
            $('#customerid').val('').trigger('change');

            initDataTable();
        });

        $('#btnExport').on('click', function() {
            exportHeaderChunk();
        });

        $('#btnCancel').on('click', function() {
            cancelExport = true;
            if (currentRequest) currentRequest.abort();
            $("#modalExport").modal('hide');
            $("#progressPercent").text("0%");
        });
    });

    // Init DataTable
    function initDataTable() {
        tbl = $('#dataTable').DataTable({
            serverSide: true,   
            processing: true,
            destroy: true,
            ajax: {
                url: '<?= base_url("salesorder/table") ?>',
                type: 'POST',
                data: function(d) {
                    d.dateFrom = $('#dateFrom').val();
                    d.dateTo = $('#dateTo').val();
                    d.customerid = $('#customerid').val();
                    d['<?= csrf_token() ?>'] = '<?= csrf_hash() ?>';
                    return d;
                }
            },
            columns: [ { data: 0 },{ data: 1 },  { data: 2 },  { data: 3 },  { data: 4 },  { data: 5 }, { data: 6 }, { data: 7 }, { data: 8 } ]
        });
    }

    function submitData() {
        let link = $('#linksubmit').val(),
            transcode = $('#transcode').val(),
            transdate = $('#transdate').val(),
            customerid = $('#customerid').val(),
            description = $('#description').val(),
            id = $('#id').val();

        $.ajax({
            url: link,
            type: 'POST',
            dataType: 'json',
            data: {
                transcode: transcode,
                transdate: transdate,
                customerid: customerid,
                description: description,
                id: id,
                <?= csrf_token() ?>: '<?= csrf_hash() ?>'
            },
            success: function(res) {
                alert(res.pesan);
                if (res.sukses === '1') {
                    $('#transcode, #transdate, #description').val("");
                    $('#customerid').val(null).trigger('change');
                    initDataTable();
                }
            },
            error: function(xhr, ajaxOptions, thrownError) {
                alert("Request gagal: " + thrownError);
            }
        });
    }

    function exportHeaderChunk() {
        let limit = 500;
        let offset = 0;
        let allData = [];
        let totalRecords = 0;

        cancelExport = false;
        $("#progressPercent").text("0%");
        $("#modalExport").modal({
            backdrop: 'static',
            keyboard: false
        }).modal('show');

        $.getJSON('salesorder/getHeaderCount', {
            dateFrom: $('#dateFrom').val(),
            dateTo: $('#dateTo').val(),
            customerid: $('#customerid').val()
        }, function(res) {
            totalRecords = res.total;

            function loadChunk() {
                if (cancelExport) {
                    $("#modalExport").modal('hide');
                    return;
                }

                currentRequest = $.getJSON('salesorder/getHeaderChunk', {
                    limit: limit,
                    offset: offset,
                    dateFrom: $('#dateFrom').val(),
                    dateTo: $('#dateTo').val(),
                    customerid: $('#customerid').val()
                }, function(data) {
                    if (cancelExport) return;

                    if (data.length > 0) {
                        allData = allData.concat(data);
                        offset += data.length;

                        let percent = Math.min(100, Math.floor((offset / totalRecords) * 100));
                        $("#progressPercent").text(percent + "%");

                        loadChunk();
                    } else {
                        currentRequest = $.ajax({
                            url: 'salesorder/export',
                            type: 'POST',
                            data: {
                                headers: JSON.stringify(allData),
                                dateFrom: $('#dateFrom').val(),
                                dateTo: $('#dateTo').val(),
                                customerid: $('#customerid').val(),
                                <?= csrf_token() ?>: '<?= csrf_hash() ?>'
                            },
                            xhrFields: {
                                responseType: 'blob'
                            },
                            success: function(blob) {
                                if (cancelExport) return;
                                $("#modalExport").modal('hide');
                                const url = window.URL.createObjectURL(blob);
                                const link = document.createElement('a');
                                link.href = url;
                                link.download = "SalesOrder_Headers.xlsx";
                                document.body.appendChild(link);
                                link.click();
                                document.body.removeChild(link);
                                window.URL.revokeObjectURL(url);
                                $("#progressPercent").text("100%");
                            },
                            error: function(xhr) {
                            },
                            complete: function() {
                                if (cancelExport) return;
                                setTimeout(() => $("#modalExport").modal('hide'), 1500);
                            }
                        });
                    }
                });
            }
            loadChunk();
        });
    }
</script>