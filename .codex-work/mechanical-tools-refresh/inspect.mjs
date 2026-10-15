import { FileBlob, SpreadsheetFile } from "@oai/artifact-tool";

const files = {
  template: "D:/WEB/laravel-inertia/outputs/01a0ae25-14a9-7f61-950e-73d6be9b917d/DAFTAR-ALAT-TEMPLATE-area12-TERISI.xlsx",
  source: "C:/Users/Service MSU/Downloads/LIST TOOLS MEKANIK TURBIN VALVE POMPA - ukuran digabung.xlsx",
};

for (const [kind, path] of Object.entries(files)) {
  const wb = await SpreadsheetFile.importXlsx(await FileBlob.load(path));
  console.log(`=== ${kind.toUpperCase()} ===`);
  console.log((await wb.inspect({
    kind: "workbook,sheet,table",
    maxChars: 12000,
    tableMaxRows: 18,
    tableMaxCols: 12,
    tableMaxCellChars: 100,
  })).ndjson);
  for (const sheet of wb.worksheets.items) {
    const used = sheet.getUsedRange();
    console.log(`--- ${sheet.name}: ${used?.address ?? "(empty)"} ---`);
    if (used) {
      const rows = used.values;
      console.log(JSON.stringify(rows.slice(0, 40)));
    }
  }
}
