<?= $this->include('template/v_header') ?>
<?= $this->include('template/v_appbar') ?>

<div class="main-content content margin-t-4">
    <div class="card-header dflex align-center justify-end  margin-r-2">
        <a href="<?= site_url('salesorder/form') ?>"
            class="btn btn-primary d-flex align-center">
            <i class="bx bx-plus-circle margin-r-2"></i>
            <span class="fw-normal fs-7">Add New</span>
        </a>
        <a href="#" class="btn btn-success btnExport margin-l-2">
            <i class="bx bx-download margin-r-2"></i>
            <span class="fw-normal fs-7">Export</span>
        </a>
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
                    <button class="btn btn-warning w-100" type="button" id="btnCancelExport">Cancel</button>
                </div>
            </div>
        </div>
    </div>
</form>
<script>
    function submitData() {
        let link = $('#linksubmit').val(),
            transcode = $('#transcode').val(),
            transdate = $('#transdate').val(),
            customername = $('#customername').val(),
            description = $('#description').val(),
            id = $('#id').val();

        $.ajax({
            url: link,
            type: 'post',
            dataType: 'json',
            data: {
                transcode: transcode,
                transdate: transdate,
                customername: customername,
                description: description,
                id: id
            },
            success: function(res) {
                if (res.sukses === '1') {
                    alert(res.pesan);
                    $('#transcode').val("");
                    $('#transdate').val("");
                    $('#customername').val("");
                    $('#description').val("");
                    tbl.ajax.reload();
                } else {
                    alert(res.pesan);
                }
            },
            error: function(xhr, ajaxOptions, thrownError) {
                alert("Request gagal: " + thrownError);
            }
        });
    }

    $(document).on("click", ".btnExport", function() {
        exportHeaderChunk();
    });

    let cancelExport = false;
    let currentRequest = null;

    function exportHeaderChunk() {
        let limit = 500;
        let offset = 0;
        let allData = [];
        let totalRecords = 0;

        cancelExport = false;
        $("#progressPercent").text("0%");
        $("#progressBar").css("width", "0%");
        $("#modalExport").modal({
            backdrop: 'static',
            keyboard: false
        }).modal('show');

        // ambil total record dulu
        $.getJSON('salesorder/getHeaderCount', function(res) {
            totalRecords = res.total;

            function loadChunk() {
                if (cancelExport) {
                    $("#modalExport").modal('hide');
                    return;
                }

                currentRequest = $.getJSON('salesorder/getHeaderChunk?limit=' + limit + '&offset=' + offset, function(data) {
                    if (cancelExport) return;

                    if (data.length > 0) {
                        allData = allData.concat(data);
                        offset += data.length;

                        // hitung persentase berdasarkan totalRecords
                        let percent = Math.min(100, Math.floor((offset / totalRecords) * 100));
                        $("#progressPercent").text(percent + "%");
                        $("#progressBar").css("width", percent + "%");

                        loadChunk();
                    } else {
                        // semua data sudah diambil → baru export
                        currentRequest = $.ajax({
                            url: 'salesorder/export',
                            type: 'POST',
                            data: {
                                headers: JSON.stringify(allData),
                                <?= csrf_token() ?>: '<?= csrf_hash() ?>'
                            },
                            xhrFields: {
                                responseType: 'blob'
                            },
                            success: function(blob) {
                                if (cancelExport) return;

                                const url = window.URL.createObjectURL(blob);
                                const link = document.createElement('a');
                                link.href = url;
                                link.download = "SalesOrder_Headers.xlsx";
                                document.body.appendChild(link);
                                link.click();
                                document.body.removeChild(link);
                                window.URL.revokeObjectURL(url);

                                $("#progressPercent").text("100%");
                                $("#progressBar").css("width", "100%");

                                setTimeout(function() {
                                    $("#modalExport").modal('hide');
                                }, 1500);
                            },
                            error: function(xhr) {
                                if (cancelExport) return;
                                console.error("Export failed:", xhr);
                                $("#modalExport").modal('hide');
                            }
                        });
                    }
                });
            }

            loadChunk();
        });
    }

    // tombol cancel
    $(document).on("click", "#btnCancelExport", function() {
        cancelExport = true;
        if (currentRequest) currentRequest.abort();
        $("#modalExport").modal('hide');
        $("#progressPercent").text("0%");
        $("#progressBar").css("width", "0%");
    });
</script>