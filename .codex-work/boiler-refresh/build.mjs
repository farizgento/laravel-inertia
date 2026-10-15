import fs from "node:fs/promises";
import { FileBlob, SpreadsheetFile } from "@oai/artifact-tool";

const inputPath = "D:/WEB/laravel-inertia/outputs/01a0ae25-14a9-7f61-950e-73d6be9b917d/DAFTAR-ALAT-TEMPLATE-area12-MEKANIK-TURBIN.xlsx";
const outputDir = "D:/WEB/laravel-inertia/outputs/01a0ae25-14a9-7f61-950e-73d6be9b917d";
const outputPath = `${outputDir}/DAFTAR-ALAT-TEMPLATE-area12-MEKANIK-BOILER.xlsx`;
const mapping = JSON.parse(await fs.readFile("mapping.json", "utf8"));

const wb = await SpreadsheetFile.importXlsx(await FileBlob.load(inputPath));
const sheet = wb.worksheets.getItem("Daftar Alat");
const beforeAE = JSON.stringify(sheet.getRange("A1:E594").values);
const names = sheet.getRange("B1:B594").values;
for (const item of mapping.mapping) {
  const actual = names[item.target_row - 1]?.[0];
  if (actual !== item.target_name) throw new Error(`Target mismatch row ${item.target_row}: ${item.target_name} vs ${actual}`);
}

const quantities = Array.from({ length: 593 }, () => [null]);
for (const [rowText, qty] of Object.entries(mapping.aggregated)) quantities[Number(rowText) - 2] = [qty];

sheet.getRange("F2:F594").clear({ applyTo: "contents" });
sheet.getRange("F2:F594").values = quantities;
wb.recalculate();
if (JSON.stringify(sheet.getRange("A1:E594").values) !== beforeAE) throw new Error("Columns A:E changed unexpectedly");

console.log((await wb.inspect({ kind: "table", range: "Daftar Alat!A1:F45", include: "values,formulas", tableMaxRows: 45, tableMaxCols: 6, maxChars: 14000 })).ndjson);
console.log((await wb.inspect({ kind: "match", searchTerm: "#REF!|#DIV/0!|#VALUE!|#NAME\\?|#N/A|#NUM!|#NULL!|#SPILL!|#CALC!", options: { useRegex: true, maxResults: 300 }, summary: "final formula error scan" })).ndjson);

await fs.mkdir(outputDir, { recursive: true });
const out = await SpreadsheetFile.exportXlsx(wb);
await out.save(outputPath);

const saved = await SpreadsheetFile.importXlsx(await FileBlob.load(outputPath));
const savedSheet = saved.worksheets.getItem("Daftar Alat");
if (JSON.stringify(savedSheet.getRange("A1:E594").values) !== beforeAE) throw new Error("Saved columns A:E changed unexpectedly");
const savedQty = savedSheet.getRange("F2:F594").values.flat();
let populated = 0, total = 0;
for (let i = 0; i < savedQty.length; i++) {
  const expected = quantities[i][0], actual = savedQty[i];
  if (actual !== expected) throw new Error(`Quantity mismatch row ${i + 2}: expected ${expected}, got ${actual}`);
  if (typeof actual === "number") { populated++; total += actual; }
}
if (populated !== mapping.populated_template_rows || total !== mapping.mapped_total) throw new Error(`Reconciliation failed ${populated}/${total}`);
console.log((await saved.inspect({ kind: "match", searchTerm: "#REF!|#DIV/0!|#VALUE!|#NAME\\?|#N/A|#NUM!|#NULL!|#SPILL!|#CALC!", options: { useRegex: true, maxResults: 300 }, summary: "saved formula error scan" })).ndjson);

for (const [name, range] of [["after-top","A1:F60"],["after-wrenches","A90:F285"],["after-misc","A340:F440"],["after-lifting","A460:F530"]]) {
  const png = await saved.render({ sheetName: "Daftar Alat", range, scale: 1.25, format: "png" });
  await fs.writeFile(`${name}.png`, new Uint8Array(await png.arrayBuffer()));
}
console.log(JSON.stringify({ outputPath, sourceRows: mapping.source_row_count, populated, total, overTotalAsset: mapping.over_total_asset.length }));
