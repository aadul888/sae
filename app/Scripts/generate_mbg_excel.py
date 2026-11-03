import sys
import json
import os
import openpyxl
from openpyxl.styles import Font, PatternFill, Alignment, Border, Side
from datetime import datetime

def generate_mbg_excel(json_path, output_path, template_path=None):
    with open(json_path, 'r', encoding='utf-8') as f:
        payload = json.load(f)

    if template_path and os.path.exists(template_path):
        wb = openpyxl.load_workbook(template_path)
    else:
        wb = openpyxl.Workbook()
        wb.remove(wb.active) # Remove default sheet

    thin_border = Border(
        left=Side(style='thin', color='D9D9D9'),
        right=Side(style='thin', color='D9D9D9'),
        top=Side(style='thin', color='D9D9D9'),
        bottom=Side(style='thin', color='D9D9D9')
    )

    data_font = Font(name='Calibri', size=11)
    center_align = Alignment(horizontal='center', vertical='center')
    left_align = Alignment(horizontal='left', vertical='center')

    note_bold = Font(name='Calibri', size=11, bold=True)
    note_italic = Font(name='Calibri', size=10, italic=True)
    note_warn = Font(name='Calibri', size=10, bold=True, color='9C0006')
    fill_warn = PatternFill(start_color='FFC7CE', end_color='FFC7CE', fill_type='solid')

    sheets_mapping = [
        ('Guru dan Tendik', payload.get('guru_tendik', [])),
        ('kelas 10', payload.get('kelas_10', [])),
        ('kelas 11', payload.get('kelas_11', [])),
        ('kelas 12', payload.get('kelas_12', [])),
    ]

    for sheet_name, rows in sheets_mapping:
        if sheet_name in wb.sheetnames:
            ws = wb[sheet_name]
            # Delete old data from row 4 downwards
            if ws.max_row >= 4:
                ws.delete_rows(4, ws.max_row - 3)
        else:
            ws = wb.create_sheet(title=sheet_name)
            # Create headers
            ws.cell(1, 1, 'NISN')
            ws.cell(1, 2, 'NIK')
            ws.cell(1, 3, 'Nama Penerima')
            ws.cell(1, 4, 'Tanggal Lahir')
            ws.cell(1, 5, 'Jenis Kelamin ID')
            ws.cell(1, 6, 'Nama Orang Tua')
            ws.cell(1, 7, 'Posisi')
            ws.cell(1, 8, 'Status Menerima')

        current_row = 4
        for item in rows:
            # Col 1: NISN
            c1 = ws.cell(row=current_row, column=1)
            nisn_val = str(item.get('nisn') or '').strip()
            if nisn_val:
                c1.value = nisn_val
                c1.number_format = '@'
            else:
                c1.value = None
            c1.alignment = center_align
            c1.font = data_font
            c1.border = thin_border

            # Col 2: NIK
            c2 = ws.cell(row=current_row, column=2)
            nik_val = str(item.get('nik') or '').strip()
            c2.value = nik_val if nik_val else None
            c2.number_format = '@'
            c2.alignment = center_align
            c2.font = data_font
            c2.border = thin_border

            # Col 3: Nama Penerima
            c3 = ws.cell(row=current_row, column=3)
            c3.value = str(item.get('nama') or '').strip()
            c3.alignment = left_align
            c3.font = data_font
            c3.border = thin_border

            # Col 4: Tanggal Lahir
            c4 = ws.cell(row=current_row, column=4)
            tgl_raw = str(item.get('tanggal_lahir') or '').strip()
            date_obj = None
            if tgl_raw:
                for fmt in ('%Y-%m-%d', '%d-%m-%Y', '%d/%m/%Y'):
                    try:
                        date_obj = datetime.strptime(tgl_raw, fmt)
                        break
                    except ValueError:
                        pass
            if date_obj:
                c4.value = date_obj
                c4.number_format = 'dd-mm-yyyy'
            else:
                c4.value = tgl_raw
            c4.alignment = center_align
            c4.font = data_font
            c4.border = thin_border

            # Col 5: Jenis Kelamin ID
            c5 = ws.cell(row=current_row, column=5)
            jk_id = item.get('jenis_kelamin_id')
            c5.value = int(jk_id) if jk_id is not None else None
            c5.alignment = center_align
            c5.font = data_font
            c5.border = thin_border

            # Col 6: Nama Orang Tua
            c6 = ws.cell(row=current_row, column=6)
            ortu = str(item.get('nama_orang_tua') or '-').strip()
            c6.value = ortu if ortu else '-'
            c6.alignment = left_align
            c6.font = data_font
            c6.border = thin_border

            # Col 7: Posisi
            c7 = ws.cell(row=current_row, column=7)
            posisi = item.get('posisi', 1)
            c7.value = int(posisi)
            c7.alignment = center_align
            c7.font = data_font
            c7.border = thin_border

            # Col 8: Status Menerima
            c8 = ws.cell(row=current_row, column=8)
            status_m = item.get('status_menerima', True)
            c8.value = bool(status_m)
            c8.alignment = center_align
            c8.font = data_font
            c8.border = thin_border

            current_row += 1

        # Append catatan footer
        current_row += 1 # Empty line
        
        c_title = ws.cell(row=current_row, column=1, value='CATATAN:')
        c_title.font = note_bold
        current_row += 1

        c_w = ws.cell(row=current_row, column=1, value='⚠ PENTING: Baris ke-3 (latar kuning) adalah DATA CONTOH — WAJIB diubah dengan data asli atau dihapus sebelum diimport!')
        c_w.font = note_warn
        c_w.fill = fill_warn
        current_row += 1

        notes = [
            '1. Isi data penerima mulai dari baris ke-3. JANGAN mengubah baris 1 (header) dan baris 2 (keterangan).',
            '2. Kolom "NISN/NIK" maksimal 20 karakter. NISN 10 digit, NIK 16 digit.',
            '3. Kolom "Jenis Kelamin ID": 392 = Laki-laki, 64 = Perempuan (lihat sheet "Ref Jenis Kelamin").',
            '4. Kolom "Posisi": 1=Penerima Utama, 2=Guru, 3=Tendik.',
            '5. Kolom "Status Menerima": isi "true" jika aktif, "false" jika tidak aktif.',
            '6. Data duplikat (NISN/NIK yang sudah ada di kelompok ini) akan dilewati secara otomatis.'
        ]
        for note in notes:
            cn = ws.cell(row=current_row, column=1, value=note)
            cn.font = note_italic
            current_row += 1

    # Ensure Ref sheets exist
    if 'Ref Jenis Kelamin' not in wb.sheetnames:
        ws_jk = wb.create_sheet('Ref Jenis Kelamin')
        ws_jk.append(['ID', 'Keterangan'])
        ws_jk.append([392, 'Laki-laki'])
        ws_jk.append([64, 'Perempuan'])

    if 'Ref Posisi' not in wb.sheetnames:
        ws_pos = wb.create_sheet('Ref Posisi')
        ws_pos.append(['ID', 'Keterangan'])
        ws_pos.append([1, 'Penerima Utama'])
        ws_pos.append([2, 'Guru'])
        ws_pos.append([3, 'Tenaga Kependidikan (Tendik)'])

    wb.save(output_path)
    print(f'SUCCESS: {output_path}')

if __name__ == '__main__':
    if len(sys.argv) < 3:
        print('Usage: python generate_mbg_excel.py <json_path> <output_path> [template_path]')
        sys.exit(1)
    j_path = sys.argv[1]
    out_path = sys.argv[2]
    tpl_path = sys.argv[3] if len(sys.argv) > 3 else None
    generate_mbg_excel(j_path, out_path, tpl_path)
