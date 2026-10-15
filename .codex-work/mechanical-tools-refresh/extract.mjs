import fs from "node:fs/promises";
import { FileBlob, SpreadsheetFile } from "@oai/artifact-tool";

const inputs = {
  template: "D:/WEB/laravel-inertia/outputs/01a0ae25-14a9-7f61-950e-73d6be9b917d/DAFTAR-ALAT-TEMPLATE-area12-TERISI.xlsx",
  source: "C:/Users/Service MSU/Downloads/LIST TOOLS MEKANIK TURBIN VALVE POMPA - ukuran digabung.xlsx",
};

const result = {};
for (const [key, path] of Object.entries(inputs)) {
  const wb = await SpreadsheetFile.importXlsx(await FileBlob.load(path));
  result[key] = wb.worksheets.items.map((sheet) => {
    const used = sheet.getUsedRange();
    return {
      name: sheet.name,
      address: used?.address ?? null,
      values: used?.values ?? [],
      formulas: used?.formulas ?? [],
    };
  });
}
await fs.writeFile("workbooks.json", JSON.stringify(result, null, 2), "utf8");
console.log("Wrote workbooks.json");
