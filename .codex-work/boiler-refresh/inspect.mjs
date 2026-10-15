import fs from "node:fs/promises";
import { FileBlob, SpreadsheetFile } from "@oai/artifact-tool";

const paths = {
  template: "D:/WEB/laravel-inertia/outputs/01a0ae25-14a9-7f61-950e-73d6be9b917d/DAFTAR-ALAT-TEMPLATE-area12-MEKANIK-TURBIN.xlsx",
  source: "C:/Users/Service MSU/Downloads/DATA TOOLS MEKANIK BOILER - ukuran digabung ke nama tools.xlsx",
};
const output = {};
for (const [key, path] of Object.entries(paths)) {
  const wb = await SpreadsheetFile.importXlsx(await FileBlob.load(path));
  console.log(`===${key.toUpperCase()}===`);
  console.log((await wb.inspect({ kind: "workbook,sheet,table", maxChars: 16000, tableMaxRows: 22, tableMaxCols: 12, tableMaxCellChars: 100 })).ndjson);
  output[key] = wb.worksheets.items.map((sheet) => {
    const used = sheet.getUsedRange();
    return { name: sheet.name, address: used?.address ?? null, values: used?.values ?? [], formulas: used?.formulas ?? [] };
  });
  if (key === "template") {
    const png = await wb.render({ sheetName: "Daftar Alat", range: "A1:F40", scale: 1.3, format: "png" });
    await fs.writeFile("template-before.png", new Uint8Array(await png.arrayBuffer()));
  }
}
await fs.writeFile("workbooks.json", JSON.stringify(output, null, 2), "utf8");
console.log("Wrote workbooks.json and template-before.png");
