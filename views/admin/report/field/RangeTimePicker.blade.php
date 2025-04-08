<div class="date-picker-container">
    <input type="text" id="dateRangeInput" placeholder="{!! $attributes['placeholder'] ?? '' !!}" class="form-control" readonly>
    <input type="hidden" name="{!! $attributes['name'] !!}" id="dateRangeInputValue_{!! $attributes['id'] !!}">
    <div class="date-picker-dropdown" id="datePickerDropdown">
        <div class="date-options">
            <div class="option" data-value="today">Hôm nay</div>
            <div class="option" data-value="yesterday">Hôm qua</div>
            <div class="option" data-value="last7days">7 ngày qua</div>
            <div class="option" data-value="last30days">30 ngày qua</div>
            <div class="option" data-value="lastWeek">Tuần trước</div>
            <div class="option" data-value="lastMonth">Tháng trước</div>
            <div class="option" data-value="lastYear">Năm trước</div>
            <div class="option" data-value="thisWeek">Tuần này</div>
            <div class="option" data-value="thisMonth">Tháng này</div>
            <div class="option" data-value="thisYear">Năm này</div>
        </div>
        <div class="custom-date">
            <label>Tùy chọn</label>
            <div class="date-inputs">
                <input type="date" id="startDate" class="form-control">
                <input type="date" id="endDate" class="form-control">
            </div>
        </div>
        <button id="filterButton">Lọc</button>
    </div>
</div>

<style>
    .date-picker-container {
        position: relative;
        width: 300px;
    }

    #dateRangeInput {
        width: 100%;
        cursor: pointer;
    }

    .date-picker-dropdown {
        display: none;
        position: absolute;
        top: 48px;
        left: 0;
        width: 100%;
        background: white;
        border: 1px solid #ccc;
        border-radius: 4px;
        box-shadow: 0 2px 5px rgba(0, 0, 0, 0.1);
        z-index: 1000;
        padding: 10px;
    }

    .date-picker-dropdown .date-options {
        display: grid;
        grid-template-columns: 1fr 1fr;
        gap: 5px;
        margin-bottom: 10px;
    }

    .date-picker-dropdown .option {
        cursor: pointer;
        position: relative;
        -webkit-box-align: center;
        align-items: center;
        -webkit-box-pack: center;
        justify-content: center;
        margin: 0px;
        user-select: none;
        text-decoration: none;
        text-align: center;
        border-radius: 6px;
        display: flex;
        width: 100%;
        min-width: 36px;
        min-height: 36px;
        padding: calc((36px - calc(1.25rem) - 2px) / 2) 16px;
        border: 1px solid rgb(211, 213, 215);
        color: rgb(15, 24, 36);
        background: rgb(255, 255, 255);
    }

    .date-picker-dropdown .option:hover {
        background-color: #f0f0f0;
    }

    .custom-date {
        margin-top: 10px;
    }

    .date-inputs {
        display: flex;
        gap: 10px;
        margin-top: 5px;
    }

    .date-inputs input {
        width: 100%;
        padding: 5px;
        border: 1px solid #ccc;
        border-radius: 4px;
    }

    #filterButton {
        width: 100%;
        padding: 8px;
        background-color: #007bff;
        color: white;
        border: none;
        border-radius: 4px;
        cursor: pointer;
        margin-top: 10px;
    }

    #filterButton:hover {
        background-color: #0056b3;
    }
</style>

<script defer>
    document.addEventListener('DOMContentLoaded', () => {
        const dateRangeInput = document.getElementById('dateRangeInput');
        const dateRangeInputValue = $('#dateRangeInputValue_{!! $attributes['id'] !!}');
        const datePickerDropdown = document.getElementById('datePickerDropdown');
        const startDateInput = document.getElementById('startDate');
        const endDateInput = document.getElementById('endDate');
        const filterButton = document.getElementById('filterButton');
        const options = document.querySelectorAll('.option');

        // Hàm định dạng ngày
        function formatDate(date) {
            const day = String(date.getDate()).padStart(2, '0');
            const month = String(date.getMonth() + 1).padStart(2, '0');
            const year = date.getFullYear();
            return `${day}/${month}/${year}`;
        }

        const setInputValue = () => {
            const startDate = dateRangeInput.dataset.startDate;
            const endDate = dateRangeInput.dataset.endDate;
            dateRangeInputValue.val(`${startDate} - ${endDate}`)
            dateRangeInputValue.trigger('change')
        };

        // Thiết lập giá trị mặc định là "7 ngày qua"
        const setDefaultLast7Days = () => {
            const today = new Date();
            const startDate = new Date(today);
            startDate.setDate(today.getDate() - 7); // 7 ngày trước
            const endDate = today;

            // Hiển thị trong ô input
            dateRangeInput.value = `7 ngày qua (${formatDate(startDate)} - ${formatDate(endDate)})`;

            // Lưu khoảng thời gian vào dataset để submit
            dateRangeInput.dataset.startDate = formatDate(startDate);
            dateRangeInput.dataset.endDate = formatDate(endDate);

            setInputValue()
        };

        // Gọi hàm thiết lập giá trị mặc định khi trang tải
        setDefaultLast7Days();

        // Hiển thị/Ẩn dropdown khi click vào ô input
        dateRangeInput.addEventListener('click', () => {
            datePickerDropdown.style.display = datePickerDropdown.style.display === 'block' ? 'none' : 'block';
        });

        // Đóng dropdown khi click bên ngoài
        document.addEventListener('click', (e) => {
            if (!datePickerDropdown.contains(e.target) && e.target !== dateRangeInput) {
                datePickerDropdown.style.display = 'none';
            }
        });

        // Xử lý khi chọn một tùy chọn thời gian
        options.forEach(option => {
            option.addEventListener('click', () => {
                const value = option.getAttribute('data-value');
                const displayText = option.textContent; // Lấy tên hiển thị (Hôm nay, Hôm qua, v.v.)
                const today = new Date();
                let startDate, endDate;

                // Tính toán khoảng thời gian
                switch (value) {
                    case 'today':
                        startDate = endDate = today;
                        break;
                    case 'yesterday':
                        startDate = endDate = new Date(today);
                        startDate.setDate(today.getDate() - 1);
                        break;
                    case 'last7days':
                        startDate = new Date(today);
                        startDate.setDate(today.getDate() - 7);
                        endDate = today;
                        break;
                    case 'last30days':
                        startDate = new Date(today);
                        startDate.setDate(today.getDate() - 30);
                        endDate = today;
                        break;
                    case 'lastWeek':
                        startDate = new Date(today);
                        startDate.setDate(today.getDate() - today.getDay() - 7);
                        endDate = new Date(startDate);
                        endDate.setDate(startDate.getDate() + 6);
                        break;
                    case 'lastMonth':
                        startDate = new Date(today.getFullYear(), today.getMonth() - 1, 1);
                        endDate = new Date(today.getFullYear(), today.getMonth(), 0);
                        break;
                    case 'lastYear':
                        startDate = new Date(today.getFullYear() - 1, 0, 1);
                        endDate = new Date(today.getFullYear() - 1, 11, 31);
                        break;
                    case 'thisWeek':
                        startDate = new Date(today);
                        startDate.setDate(today.getDate() - today.getDay());
                        endDate = today;
                        break;
                    case 'thisMonth':
                        startDate = new Date(today.getFullYear(), today.getMonth(), 1);
                        endDate = today;
                        break;
                    case 'thisYear':
                        startDate = new Date(today.getFullYear(), 0, 1);
                        endDate = today;
                        break;
                }

                // Hiển thị tên tùy chọn kèm ngày trong ô input
                if (startDate.getTime() === endDate.getTime()) {
                    // Nếu ngày bắt đầu và kết thúc giống nhau (Hôm nay, Hôm qua)
                    dateRangeInput.value = `${displayText} (${formatDate(startDate)})`;
                } else
                {
                    // Nếu là khoảng thời gian (7 ngày qua, 30 ngày qua, v.v.)
                    dateRangeInput.value = `${displayText} (${formatDate(startDate)} - ${formatDate(endDate)})`;
                }

                // Lưu khoảng thời gian vào thuộc tính data để submit
                dateRangeInput.dataset.startDate = formatDate(startDate);
                dateRangeInput.dataset.endDate = formatDate(endDate);
                datePickerDropdown.style.display = 'none';

                setInputValue()
            });
        });

        // Xử lý khi nhấn nút "Lọc" với ngày tùy chỉnh
        filterButton.addEventListener('click', () => {
            const startDate = startDateInput.value;
            const endDate = endDateInput.value;
            if (startDate && endDate) {
                const formattedStartDate = formatDate(new Date(startDate));
                const formattedEndDate = formatDate(new Date(endDate));
                dateRangeInput.value = `${formattedStartDate} - ${formattedEndDate}`;
                datePickerDropdown.style.display = 'none';
                // Lưu khoảng thời gian vào thuộc tính data để submit
                dateRangeInput.dataset.startDate = formattedStartDate;
                dateRangeInput.dataset.endDate = formattedEndDate;
                setInputValue()
            }
        });
    });
</script>