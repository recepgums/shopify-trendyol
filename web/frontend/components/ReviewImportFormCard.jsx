import React, { useState, useEffect } from "react";
import { LegacyCard, Form, TextField, Select, Button, Checkbox, FormLayout } from "@shopify/polaris";
import { Toast } from "@shopify/app-bridge-react";
import { useTranslation } from "react-i18next";
import { useAppQuery, useAuthenticatedFetch } from "../hooks";

export function ReviewImportFormCard() {
    const emptyToastProps = { content: null };
    const [isLoading, setIsLoading] = useState(false);
    const [productOptions, setProductOptions] = useState([]);
    const [toastProps, setToastProps] = useState(emptyToastProps);
    const fetch = useAuthenticatedFetch();
    const { t } = useTranslation();
    const productsCount = 5;

    const [productLink, setProductLink] = useState('');
    const [targetProduct, setTargetProduct] = useState('');
    const [reviewCountLimit, setReviewCountLimit] = useState(0);
    const [selectedStars, setSelectedStars] = useState([]);
    const [photoType, setPhotoType] = useState('text');

    const handleProductLinkChange = (value) => setProductLink(value);
    const handleTargetProductChange = (value) => setTargetProduct(value);
    const handleReviewCountLimitChange = (value) => setReviewCountLimit(value);
    const handleStarsChange = (value) => setSelectedStars(value);
    const handlePhotoTypeChange = (value) => setPhotoType(value);
    const fetchProductOptions = async () => {
        try {
            const response = await fetch("/api/products");
            if (response.ok) {
                const data = await response.json();
                setProductOptions(data.products);
            } else {
                console.error("Error fetching product options");
            }
        } catch (error) {
            console.error("Error fetching product options:", error);
        }
    };

    useEffect(() => {
        fetchProductOptions();
    }, []);

    const toastMarkup = toastProps.content && (
        <Toast {...toastProps} onDismiss={() => setToastProps(emptyToastProps)} />
    );

    const handleImportReviews = async () => {
        try {
            const trendyolResponse = await fetch("/api/trendyol-review/download", {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                },
                body: JSON.stringify({
                    productLink,
                    targetProduct,
                    reviewCountLimit,
                    selectedStars,
                    photoType,
                }),
            });
            if (trendyolResponse.ok) {
                const blob = await trendyolResponse.blob();

                // Create a blob URL for the CSV file
                const url = window.URL.createObjectURL(new Blob([blob]));

                // Create an anchor element to trigger the download
                const a = document.createElement('a');
                a.href = url;
                a.download = 'comment_chatgpt.csv';
                document.body.appendChild(a);
                a.click();
                document.body.removeChild(a);

                // Clean up the blob URL
                window.URL.revokeObjectURL(url);
            } else {
                console.error("Error fetching Trendyol product options");
            }
        } catch (error) {
            console.error("Error fetching data:", error);
        }
    };


    return (
        <>
            {toastMarkup}
            <LegacyCard
                title={"Import Reviews from Trendyol Product link"}
                sectioned
                primaryFooterAction={{
                    content: t("ProductsCard.populateProductsButton", {
                        count: productsCount,
                    }),
                    onAction: handleImportReviews,
                    loading: isLoading,
                }}
            >
                <Form onSubmit={handleImportReviews}>
                    <FormLayout>
                        <TextField
                            label="Trendyol Product Link"
                            value={productLink}
                            onChange={handleProductLinkChange}
                            type="text"
                        />
                        {productOptions && (
                            <Select
                                label="Target Shopify Product"
                                options={productOptions.map((option) => ({
                                    label: option.title,
                                    value: option.id,
                                }))}
                                value={targetProduct}
                                onChange={handleTargetProductChange}
                            />
                        )}
                        <TextField
                            label="Review Count Limit"
                            value={reviewCountLimit.toString()}
                            onChange={handleReviewCountLimitChange}
                            type="number"
                        />
                        <Checkbox
                            label="Review Star options"
                            options={[
                                { label: '1', value: '1' },
                                { label: '2', value: '2' },
                                { label: '3', value: '3' },
                                { label: '4', value: '4' },
                                { label: '5', value: '5' },
                            ]}
                            selected={selectedStars}
                            onChange={handleStarsChange}
                        />
                        <Select
                            label="Photo Type"
                            options={[
                                { label: 'Only Text', value: 'text' },
                                { label: 'Only Image', value: 'image' },
                                { label: 'Mixed', value: 'mixed' },
                            ]}
                            value={photoType}
                            onChange={handlePhotoTypeChange}
                        />
                    </FormLayout>
                </Form>
            </LegacyCard>
        </>
    );
}
