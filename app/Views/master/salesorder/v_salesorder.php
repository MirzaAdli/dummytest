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

        function exportHeaderChunk() {
            let limit = 500;
            let offset = 0;
            let allData = [];

            function loadChunk() {
                console.log("Request chunk offset:", offset);
                $.getJSON('salesorder/getHeaderChunk?limit=' + limit + '&offset=' + offset, function(data) {
                    if (data.length > 0) {
                        allData = allData.concat(data);
                        offset += limit;
                        loadChunk(); // ambil batch berikutnya
                    } else {
                        console.log("Total data terkumpul:", allData.length);

                        // kirim ke controller
                        $.ajax({
                            url: 'salesorder/export',
                            type: 'POST',
                            data: {
                                headers: JSON.stringify(allData)
                            },
                            xhrFields: {
                                responseType: 'blob'
                            },
                            success: function(blob) {
                                console.log("Blob size:", blob.size);
                                const url = window.URL.createObjectURL(blob);
                                const link = document.createElement('a');
                                link.href = url;
                                link.download = "SalesOrderHeaders_All.xlsx";
                                document.body.appendChild(link);
                                link.click();
                                document.body.removeChild(link);
                                window.URL.revokeObjectURL(url);
                            },
                            error: function(xhr) {
                                console.error("Export failed:", xhr);
                            }
                        });
                    }
                });
            }

            loadChunk();
        }
    </script>