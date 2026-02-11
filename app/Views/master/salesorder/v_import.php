<form id="importexcel" style="padding-inline: 0px;">
    <div class="row">
        <div>
            <div class="form-group">
                <label class="required">Excel File</label>
                <input type="file" id="excelfile" accept=".xlsx, .xls" class="form-input" style="padding: 8px;pointer-events: unset !important;">
            </div>
        </div>
    </div>
    <div id="loading-alltrans" class="hiding mt-3">
        <h4>
            <i class='bx bx-loader-circle bx-spin text-info'></i>
            Processing <span class="text-primary" id="progressPercent">0%</span>
        </h4>
    </div>
    <div class="modal-footer dflex" style="justify-content: space-between !important;">
        <button style="margin: 0 !important;" class="btn btn-info dflex align-center justify-center" type="button" onclick="downloadTemplate()">
            <i class="bx bx-download margin-r-2"></i>
            <span class="fw-normal fs-7">Template</span>
        </button>
        <div style="margin-left: 0 !important; margin-right: 0 !important;" class="dflex">
            <button class="btn btn-warning dflex button-cancel align-center margin-r-2" type="button" onclick="close_modal('modaldetail')">
                <i class="bx bx-x margin-r-2"></i>
                <span class="fw-normal fs-7">Cancel</span>
            </button>
            <button class="btn btn-primary dflex button-import align-center" type="submit">
                <i class="bx bx-check margin-r-2"></i>
                <span class="fw-normal fs-7">Process</span>
            </button>
        </div>
    </div>
</form>
<script>
    $(document).ready(function() {
        $("#importexcel").on('submit', function(e) {
            e.preventDefault();
            $(".button-import").attr('disabled', 'disabled');
            $("#excelfile").attr('onchange', 'getSOFiles(event)');
            $("#excelfile").trigger('change');
            $("#loading-alltrans").removeClass('hiding');
            $('#excelfile').attr('disabled', 'disabled');
            return false;
        });

        $("#btnCancelImport").on('click', function() {
            isCancelled = true;
            showNotif("error", "Import dibatalkan");
            setTimeout(() => {
                $("#loading-alltrans").addClass('hiding');
            }, 500);
        });
    });

    let totalRows = 0;
    let sentRows = 0;
    let isCancelled = false;
    let undfhSO = 0;

    function downloadTemplate() {
        var url = '<?= base_url('public/downloadable/TemplateSalesOrder.xlsx') ?>';
        window.location.href = url;
    }

    async function getSOFiles(e) {
        e = e || window.event;
        let file = e.target.files[0];
        let data = await file.arrayBuffer();
        let wb = XLSX.read(data);
        let ws = wb.Sheets[wb.SheetNames[0]];

        let last_key = Object.keys(ws).filter(key => !key.startsWith('!'));
        let getlen = last_key[last_key.length - 1].replace(/[^0-9\.]/g, '');

        let arr = [];
        let offset = 100;
        let keys = 0;

        totalRows = getlen - 3;
        sentRows = 0;
        isCancelled = false;

        $("#loading-alltrans").removeClass('hiding');

        for (let o = 4; o <= getlen * 1; o++) {
            if (isCancelled) break;

            if (ws['A' + o] && ws['A' + o].v !== undefined) {
                keys++;
                arr.push([
                    ws['A' + o]?.v ?? '', // Transcode
                    ws['B' + o]?.v ?? '', // Transdate
                    ws['C' + o]?.v ?? '', // Customer Name
                    ws['D' + o]?.v ?? '', // Grand Total
                    ws['E' + o]?.v ?? '', // Description
                ]);
            }

            if (keys == offset) {
                keys = 0;
                await sendSOData(arr);
                arr = [];
            }
        }

        if (!isCancelled && arr.length > 0) {
            await sendSOData(arr, 't');
        }
    }

    async function sendSOData(arr, isfinish = 'f') {
        if (isCancelled) return;

        await sleep(500);

        sentRows += arr.length;
        let percentComplete = Math.round((sentRows / totalRows) * 100);

        $("#progressPercent").text(percentComplete + "%");
        $("#progressBar").css('width', percentComplete + '%').text(percentComplete + '%');

        $.ajax({
            url: '<?= base_url('salesorder/importExcel') ?>',
            type: 'post',
            dataType: 'json',
            data: {
                datas: JSON.stringify(arr),
                <?= csrf_token() ?>: decrypter($("#csrf_token").val())
            },
            success: function(res) {
                $("#csrf_token").val(encrypter(res.csrfToken));
                undfhSO += res.undfhSO;

                if (isfinish == 't' && !isCancelled) {
                    $("#progressPercent").text("100%");
                    showNotif("success", "Sales Order updated successfully");
                    if (undfhSO >= 1) {
                        showNotif("error", `${undfhSO} sales order dilewatkan`);
                    }
                    setTimeout(() => {
                        close_modal('modaldetail');
                        $('#dataTable').DataTable().ajax.reload();
                    }, 500);
                }
            }
        });
    }
</script>