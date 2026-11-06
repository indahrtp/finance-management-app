# Analisis Permasalahan

## Data
1. Master Kategori Chart of Account (CoA) = nama kategori
2. Master Chart of Account = kode, nama, kategori 
3. Transaksi = tanggal, kode_coa, nama_coa, deskripsi, debit, dan kredit
4. Laporan Profil/Loss = baris perkategori, kolom perbulan

## Temuan
1. Income = credit - debit
2. Expense = debit - credit
3. Laporan mengelompokkan total income dan expense sehingga dibutuhkan type pada tabel kategori CoA
4. Struktur laporan 
```
Salary
Other Income
Total Income = Jumlah semua kategori income
Family Expense
Transport Expense
Meal Expense
Total Expense = Jumlah semua kategori expense
Net Income = Total Income - Total Expense
```

## Business Rules
1. Data tabel di transaksi tidak boleh duplikat
2. Kode CoA harus unik
3. Nama kategori harus unik
4. Satu transaksi hanya mengisi debit atau kredit, tidak keduanya dan tidak keduanya kosong, nilai harus lebih dari 0
5. CoA bertipe income hanya boleh menggunakan kredit
6. CoA bertipe expense hanya boleh menggunakan debit
7. Kategori CoA tidak boleh dihapus jika sudah digunakan (restrict) / menggunakna soft delete
8. Nominal disimpan sebagai desimal (12,5)

## Tabel
| Nama Tabel | Atribut | Wajib? |
|---|---|---|
| users | name, email, password | Ya |
| categories | name, type | Ya |
| coa | code, name, category_id | Ya |
| transactions | date, coa_id, description, debit, credit, user_id | Ya |
| budgets | category_id, period (YYYY-MM), amount | Optional |

## Gambaran Query Laporan
```sql
SELECT c.name AS category, c.type,
       TO_CHAR(t.date, 'YYYY-MM') AS period,
       SUM(CASE WHEN c.type = 'income'
                THEN t.credit - t.debit
                ELSE t.debit - t.credit END) AS amount
FROM transactions t
JOIN chart_of_accounts a ON a.id = t.coa_id
JOIN categories c        ON c.id = a.category_id
GROUP BY c.name, c.type, period
ORDER BY period;
```