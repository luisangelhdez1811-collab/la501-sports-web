import sys
import json
import os
import warnings
import joblib
import numpy as np

warnings.filterwarnings("ignore")

BASE_DIR = os.path.dirname(os.path.abspath(__file__))
MODELS_DIR = os.path.join(BASE_DIR, "..", "storage", "ml_models")

os.environ["JOBLIB_MULTIPROCESSING"] = "0"
os.environ["LOKY_MAX_CPU_COUNT"] = "1"


def cmd_segmentacion(payload):
    kmeans = joblib.load(os.path.join(MODELS_DIR, "kmeans_segmentacion.joblib"))
    scaler = joblib.load(os.path.join(MODELS_DIR, "scaler_segmentacion.joblib"))
    centros_reales = scaler.inverse_transform(kmeans.cluster_centers_)
    ranking = []
    for idx, c in enumerate(centros_reales):
        frecuencia, monetario, recencia, ticket, frec_mensual = c
        score = frecuencia + monetario / 100 + frec_mensual * 5 - recencia / 10
        ranking.append((idx, score))
    ranking.sort(key=lambda x: x[1], reverse=True)
    nombres = {ranking[0][0]: "VIP", ranking[1][0]: "Casual", ranking[2][0]: "Inactivo"}

    clientes = payload["clientes"]
    X = np.array([[c["frecuencia"], c["monetario"], c["recencia_dias"],
                    c["ticket_promedio"], c["frecuencia_mensual"]] for c in clientes])
    X_scaled = scaler.transform(X)
    clusters = kmeans.predict(X_scaled)

    resultados = []
    for c, cluster_id in zip(clientes, clusters):
        resultados.append({
            "id": c["id"],
            "cluster": int(cluster_id),
            "segmento": nombres[int(cluster_id)],
        })
    return {"resultados": resultados}


def cmd_demanda_total(payload):
    modelo = joblib.load(os.path.join(MODELS_DIR, "modelo_demanda_total_diaria.joblib"))
    modelo.n_jobs = 1
    dias = payload["dias"]
    X = np.array([[d["dia_semana"], d["es_fin_de_semana"], d["dia_mes"], d["mes"]] for d in dias])
    preds = modelo.predict(X)
    preds = np.clip(preds, 0, None)
    return {"predicciones": [round(float(p), 1) for p in preds]}


def cmd_demanda_productos(payload):
    modelo = joblib.load(os.path.join(MODELS_DIR, "modelo_demanda.joblib"))
    modelo.n_jobs = 1
    columnas = joblib.load(os.path.join(MODELS_DIR, "modelo_demanda_columnas.joblib"))
    prod_cols = [c for c in columnas if c.startswith("prod_")]
    prod_ids = [int(c.replace("prod_", "")) for c in prod_cols]

    dias = payload["dias"]
    filas = []
    meta = []
    for d in dias:
        for pid, pcol in zip(prod_ids, prod_cols):
            fila = {c: 0 for c in columnas}
            fila["dia_semana"] = d["dia_semana"]
            fila["es_fin_de_semana"] = d["es_fin_de_semana"]
            fila["dia_mes"] = d["dia_mes"]
            fila["mes"] = d["mes"]
            fila[pcol] = 1
            filas.append([fila[c] for c in columnas])
            meta.append({"fecha_idx": d["idx"], "product_id": pid})

    X = np.array(filas)
    preds = np.clip(modelo.predict(X), 0, None)

    resultados = {}
    for m, p in zip(meta, preds):
        resultados.setdefault(m["fecha_idx"], []).append({
            "product_id": m["product_id"],
            "unidades": round(float(p), 2),
        })
    return {"resultados": resultados}


def cmd_recomendacion_similares(payload):
    sim = joblib.load(os.path.join(MODELS_DIR, "similitud_coseno_productos.joblib"))
    promedio = sim.mean(axis=0).sort_values(ascending=False)
    top_n = payload.get("top_n", 4)
    nombres = list(promedio.head(top_n).index)
    return {"productos": nombres}


COMANDOS = {
    "segmentacion": cmd_segmentacion,
    "demanda_total": cmd_demanda_total,
    "demanda_productos": cmd_demanda_productos,
    "recomendacion_similares": cmd_recomendacion_similares,
}


def main():
    comando = sys.argv[1] if len(sys.argv) > 1 else None
    payload = json.loads(sys.stdin.read() or "{}")
    if comando not in COMANDOS:
        print(json.dumps({"error": f"comando desconocido: {comando}"}))
        sys.exit(1)
    try:
        resultado = COMANDOS[comando](payload)
        print(json.dumps(resultado))
    except Exception as exc:
        print(json.dumps({"error": str(exc)}))
        sys.exit(1)


if __name__ == "__main__":
    main()
