<form id="importexcel" style="padding-inline: 0px;">
    <div class="row">
        <div>
            <div class="form-group">
                <label class="required">Excel File</label>
                <input type="file" name="excelfile" id="excelfile" accept=".xlsx, .xls" class="form-input" style="padding: 8px;pointer-events: unset !important;">
            </div>
        </div>
    </div>
    <div id="loading-alltrans" class="hiding">
        <h4>
            <i class='bx bx-loader-circle bx-spin text-info'></i> Processing <span class="text-primary" id="totalsent">0</span> / <span id="alltotals" class="text-primary">100</span>
        </h4>
    </div>
    <div class="modal-footer dflex" style="justify-content: space-between !important;">
        <button style="margin: 0 !important;" class="btn btn-info dflex align-center justify-center" type="button" onclick="downloadTemplate()">
            <i class="bx bx-download margin-r-2"></i>
            <span class="fw-normal fs-7">Template</span>
        </button>
        <div style="margin-left: 0 !important; margin-right: 0 !important;" class="dflex">
            <button class="btn btn-warning dflex button-import align-center margin-r-2" type="button" onclick="close_modal('modaldetail')">
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

        let last_key = Object.keys(ws);
        last_key = last_key.filter(key => !key.startsWith('!'));
        let getlen = last_key[last_key.length - 1].replace(/[^0-9\.]/g, '');

        let arr = [];
        let offset = 100;
        let keys = 0;

        // total baris (dikurangi header)
        $("#alltotals").text(formatRupiah(getlen - 3));

        // mulai dari baris ke-4 (skip judul + header)
        for (let o = 4; o <= getlen * 1; o++) {
            if (ws['A' + o] && ws['A' + o].v !== undefined) {
                keys++;
                arr.push([
                    ws['A' + o]?.v ?? '', // Transcode
                    ws['C' + o]?.v ?? '', // Transdate
                    ws['E' + o]?.v ?? '', // Customer Name
                    ws['G' + o]?.v ?? '', // Grand Total
                    ws['I' + o]?.v ?? '', // Description
                ]);
            }

            if (keys == offset) {
                keys = 0;
                sendSOData(arr);
                arr = [];
            }
        }

        // kirim batch terakhir
        if (arr.length > 0) {
            sendSOData(arr, 't');
        }
    }

    $(document).ready(function() {
        $("#importexcelSO").on('submit', function(e) {
            e.preventDefault();
            $(".button-import").attr('disabled', 'disabled');
            $("#excelfileSO").attr('onchange', 'getSOFiles(event)');
            $("#btn-close-modaldetail").addClass('hiding')
            $("#excelfileSO").trigger('change');
            $("#loading-alltrans").removeClass('hiding');
            $('#excelfileSO').attr('disabled', 'disabled')
            return false;
        })
    })

    undfhSO = 0

    async function sendSOData(arr, isfinish = 'f') {
        let textproses = $("#totalsent").text();
        $("#totalsent").text(formatRupiah(exp_number(textproses) + arr.length));

        $.ajax({
            url: '<?= base_url('salesorder/importExcel') ?>',
            type: 'post',
            dataType: 'json',
            data: {
                datas: JSON.stringify(arr),
                <?= csrf_token() ?>: decrypter($("#csrf_token").val())
            },
            async: true,
            success: function(res) {
                $('#excelfileSO').removeAttr('disabled');
                $("#csrf_token").val(encrypter(res.csrfToken));
                undfhSO += res.undfhSO
                if (isfinish == 't') {
                    showNotif("success", "Sales Order updated successfully");
                    if (undfhSO >= 1) {
                        showNotif("error", `${undfhSO} sales order dilewatkan`);
                    }
                    setTimeout(() => {
                        close_modal('modaldetail');
                        $('#dataTable').DataTable().ajax.reload();
                    }, 250);
                }
                $(".button-import").removeAttr('disabled')
                $("#btn-close-modaldetail").removeClass('hiding')
            }
        })
    }
</script>