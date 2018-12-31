
public class record {
	String title;
	Double[] prices;
	Double median;
	record(String title, Double[] prices, Double median){
		this.title = title;
		this.prices = prices;
		this.median = median;
	}
	//Since the scraper only knows the title at first, we need a contructor for just the title
	record(String title){
		this.title = title;
		this.prices = new Double[12];
		this.median = null;
	}
	
	//Setter methods
	public void setPrice(Double price, int index) {
		this.prices[index] = price;
	}
	public void setMedian(Double median) {
		this.median = median;
	}
	
	//compares given title to title of this object
	public boolean isTitle(String title) {
		return this.title.equals(title);
	}
	
}
